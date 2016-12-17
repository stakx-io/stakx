<?php

namespace allejo\stakx\Object;

use allejo\stakx\Core\StakxLogger;
use allejo\stakx\Manager\AssetManager;
use allejo\stakx\Manager\PageManager;
use allejo\stakx\Manager\ThemeManager;
use allejo\stakx\System\FileExplorer;
use allejo\stakx\System\Filesystem;
use allejo\stakx\Manager\CollectionManager;
use allejo\stakx\Manager\DataManager;
use allejo\stakx\System\Folder;
use JasonLewis\ResourceWatcher\Event;
use JasonLewis\ResourceWatcher\Resource\FileResource;
use JasonLewis\ResourceWatcher\Tracker;
use JasonLewis\ResourceWatcher\Watcher;
use Symfony\Component\Console\Output\OutputInterface;

class Website
{
    /**
     * The location of where the compiled website will be written to
     *
     * @var Folder
     */
    private $outputDirectory;

    /**
     * The main configuration to be used to build the specified website
     *
     * @var Configuration
     */
    private $configuration;

    /**
     * When set to true, the Stakx website will be built without a configuration file
     *
     * @var bool
     */
    private $confLess;

    /**
     * When set to true, Twig templates will not have access to filters or functions which provide access to the
     * filesystem
     *
     * @var bool
     */
    private $safeMode;

    /**
     * When set to true, Stakx will not clean the _site folder after a rebuild
     *
     * @var bool
     */
    private $noClean;

    /**
     * @var StakxLogger
     */
    private $output;

    /**
     * @var AssetManager
     */
    private $am;

    /**
     * @var CollectionManager
     */
    private $cm;

    /**
     * @var DataManager
     */
    private $dm;

    /**
     * @var Filesystem
     */
    private $fs;

    /**
     * @var PageManager
     */
    private $pm;

    /**
     * @var ThemeManager
     */
    private $tm;

    /**
     * Website constructor.
     *
     * @param OutputInterface $output
     */
    public function __construct (OutputInterface $output)
    {
        $this->output = new StakxLogger($output);
        $this->cm = new CollectionManager();
        $this->dm = new DataManager();
        $this->pm = new PageManager();
        $this->fs = new Filesystem();
    }

    /**
     * Compile the website.
     *
     * @param bool $tracking Whether or not to keep track of files as they're compiled to save time in 'watch'
     */
    public function build ($tracking = false)
    {
        // Configure the environment
        $this->createFolderStructure(!$this->noClean);

        // Our output directory
        $this->outputDirectory = new Folder($this->getConfiguration()->getTargetFolder());
        $this->outputDirectory->setTargetDirectory($this->getConfiguration()->getBaseUrl());

        // Parse DataItems
        $this->dm->setLogger($this->output);
        $this->dm->enableTracking($tracking);
        $this->dm->parseDataItems($this->getConfiguration()->getDataFolders());
        $this->dm->parseDataSets($this->getConfiguration()->getDataSets());

        // Prepare Collections
        $this->cm->setLogger($this->output);
        $this->cm->enableTracking($tracking);
        $this->cm->parseCollections($this->getConfiguration()->getCollectionsFolders());

        // Handle PageViews
        $this->pm->setLogger($this->output);
        $this->pm->setTargetFolder($this->outputDirectory);
        $this->pm->setCollections($this->cm->getCollections());
        $this->pm->setRedirectTemplate($this->getConfiguration()->getRedirectTemplate());
        $this->pm->enableTracking($tracking);
        $this->pm->parsePageViews($this->getConfiguration()->getPageViewFolders());
        $this->pm->configureTwig($this->getConfiguration(), array(
            'safe'    => $this->safeMode,
            'globals' => array(
                array('name' => 'site',        'value' => $this->getConfiguration()->getConfiguration()),
                array('name' => 'collections', 'value' => $this->cm->getCollections()),
                array('name' => 'menu',        'value' => $this->pm->getSiteMenu()),
                array('name' => 'data',        'value' => $this->dm->getDataItems())
            )
        ));
        $this->pm->compileAll();

        // At this point, we are looking at static files to copy over meaning we need to ignore all of the files that
        // make up the source of a stakx website
        $assetsToIgnore = array_merge(
            Configuration::$stakxSourceFiles,
            $this->getConfiguration()->getExcludes()
        );

        //
        // Theme Management
        //
        $theme = $this->configuration->getTheme();

        if (!is_null($theme))
        {
            $this->output->notice("Looking for '${theme}' theme...");

            $this->tm = new ThemeManager($theme);
            $this->tm->configureFinder($this->getConfiguration()->getIncludes(), $assetsToIgnore);
            $this->tm->setLogger($this->output);
            $this->tm->enableTracking($tracking);
            $this->tm->setFolder($this->outputDirectory);
            $this->tm->copyFiles();
        }

        //
        // Static file management
        //
        $this->am = new AssetManager();
        $this->am->configureFinder($this->getConfiguration()->getIncludes(), $assetsToIgnore);
        $this->am->setLogger($this->output);
        $this->am->setFolder($this->outputDirectory);
        $this->am->enableTracking($tracking);
        $this->am->copyFiles();
    }

    public function watch ()
    {
        $this->output->writeln('Building website...');
        $this->build(true);
        $this->output->writeln(sprintf('Watching %s', getcwd()));

        $fileExplorer = FileExplorer::create(
            getcwd(),
            array_merge($this->getConfiguration()->getExcludes(), array(
                $this->getConfiguration()->getTargetFolder()
            )),
            $this->getConfiguration()->getIncludes()
        );
        $tracker    = new Tracker();
        $watcher    = new Watcher($tracker, $this->fs);
        $listener   = $watcher->watch(getcwd(), $fileExplorer->getExplorer());
        $targetPath = $this->getConfiguration()->getTargetFolder();

        $this->output->writeln('Watch started successfully');

        $listener->onAnything(function (Event $event, FileResource $resouce, $path) use ($targetPath)
        {
            $filePath = $this->fs->getRelativePath($path);

            try
            {
                switch ($event->getCode())
                {
                    case Event::RESOURCE_CREATED:
                        $this->creationWatcher($filePath);
                        break;

                    case Event::RESOURCE_MODIFIED:
                        $this->modificationWatcher($filePath);
                        break;
                }
            }
            catch (\Exception $e)
            {
                $this->output->error(sprintf("Your website failed to build with the following error: %s",
                    $e->getMessage()
                ));
            }
        });

        $watcher->start();
    }

    /**
     * @return Configuration
     */
    public function getConfiguration ()
    {
        return $this->configuration;
    }

    /**
     * @param string $configFile
     *
     * @throws \LogicException
     */
    public function setConfiguration ($configFile)
    {
        if (!$this->fs->exists($configFile) && !$this->isConfLess())
        {
            $this->output->error("You are trying to build a website in a directory without a configuration file. Is this what you meant to do?");
            $this->output->error("To build a website without a configuration, use the '--no-conf' option");

            throw new \LogicException("Cannot build a website without a configuration when not in Configuration-less mode");
        }

        if ($this->isConfLess())
        {
            $configFile = "";
        }

        $this->configuration = new Configuration();
        $this->configuration->setLogger($this->output);
        $this->configuration->parseConfiguration($configFile);
    }

    /**
     * Get whether or not the website is being built in Configuration-less mode
     *
     * @return bool True when being built with no configuration file
     */
    public function isConfLess ()
    {
        return $this->confLess;
    }

    /**
     * Set whether or not the website should be built with a configuration
     *
     * @param bool $status True when a website should be built without a configuration
     */
    public function setConfLess ($status)
    {
        $this->confLess = $status;
    }

    /**
     * Get whether or not the website is being built in safe mode.
     *
     * Safe mode is defined as disabling file system access from Twig and disabling user Twig extensions
     *
     * @return bool True when the website is being built in safe mode
     */
    public function isSafeMode ()
    {
        return $this->safeMode;
    }

    /**
     * Set whether a website should be built in safe mode
     *
     * @param bool $bool True if a website should be built in safe mode
     */
    public function setSafeMode ($bool)
    {
        $this->safeMode = $bool;
    }

    /**
     * @return boolean
     */
    public function isNoClean()
    {
        return $this->noClean;
    }

    /**
     * @param boolean $noClean
     */
    public function setNoClean($noClean)
    {
        $this->noClean = $noClean;
    }

    private function creationWatcher ($filePath)
    {
        $this->output->writeln(sprintf("File creation detected: %s", $filePath));

        if ($this->pm->isHandled($filePath))
        {
            $this->pm->createNewItem($filePath);
            $this->pm->refreshItem($filePath);
        }
        else if ($this->cm->isHandled($filePath))
        {
            $contentItem = $this->cm->createNewItem($filePath);

            $this->pm->updateTwigVariable('collections', $this->cm->getCollections());
            $this->pm->updatePageView($contentItem);
            $this->pm->compileContentItem($contentItem);
            $this->pm->compileSome(array(
                'namespace' => 'collections',
                'dependency' => $contentItem->getCollection()
            ));
        }
        else if ($this->dm->isHandled($filePath))
        {
            $change = $this->dm->createNewItem($filePath);

            $this->pm->updateTwigVariable('data', $this->dm->getDataItems());
            $this->pm->compileSome(array(
                'namespace' => 'data',
                'dependency' => $change
            ));
        }
        else if ($this->tm->isHandled($filePath))
        {
            $this->tm->createNewItem($filePath);
        }
        else if ($this->am->isHandled($filePath))
        {
            $this->am->createNewItem($filePath);
        }
    }

    private function modificationWatcher ($filePath)
    {
        $this->output->writeln(sprintf("File change detected: %s", $filePath));

        if ($this->pm->isTracked($filePath))
        {
            $this->pm->refreshItem($filePath);
        }
        else if ($this->cm->isTracked($filePath))
        {
            $contentItem = &$this->cm->getContentItem($filePath);
            $contentItem->refreshFileContent();

            $this->pm->compileContentItem($contentItem);
            $this->pm->compileSome(array(
                'namespace' => 'collections',
                'dependency' => $contentItem->getCollection()
            ));
        }
        else if ($this->dm->isTracked($filePath))
        {
            $change = $this->dm->refreshItem($filePath);

            $this->pm->updateTwigVariable('data', $this->dm->getDataItems());
            $this->pm->compileSome(array(
                'namespace' => 'data',
                'dependency' => $change
            ));
        }
        else if ($this->tm->isTracked($filePath))
        {
            $this->tm->refreshItem($filePath);
        }
        else if ($this->am->isTracked($filePath))
        {
            $this->am->refreshItem($filePath);
        }
    }

    /**
     * Prepare the Stakx environment by creating necessary cache folders
     *
     * @param bool $cleanDirectory Clean the target directory
     */
    private function createFolderStructure ($cleanDirectory)
    {
        $tarDir = $this->fs->absolutePath($this->configuration->getTargetFolder());

        if ($cleanDirectory)
        {
            $this->fs->remove($tarDir);
        }

        $this->fs->remove($this->fs->absolutePath('.stakx-cache'));
        $this->fs->mkdir('.stakx-cache/twig');
        $this->fs->mkdir($tarDir);
    }
}