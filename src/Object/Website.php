<?php

namespace allejo\stakx\Object;

use allejo\stakx\Core\ConsoleInterface;
use allejo\stakx\Manager\AssetManager;
use allejo\stakx\Manager\PageManager;
use allejo\stakx\Manager\ThemeManager;
use allejo\stakx\System\Filesystem;
use allejo\stakx\Manager\CollectionManager;
use allejo\stakx\Manager\DataManager;
use allejo\stakx\System\Folder;
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
     * @var ConsoleInterface
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
        $this->output = new ConsoleInterface($output);
        $this->cm = new CollectionManager();
        $this->dm = new DataManager();
        $this->pm = new PageManager();
        $this->fs = new Filesystem();
    }

    /**
     * Compile the website.
     *
     * @param bool $cleanDirectory Clean the target directing before rebuilding
     * @param bool $tracking       Whether or not to keep track of files as they're compiled to save time in 'watch'
     */
    public function build ($cleanDirectory, $tracking = false)
    {
        // Configure the environment
        $this->createFolderStructure($cleanDirectory);

        // Parse DataItems
        $this->dm->setConsoleOutput($this->output);
        $this->dm->parseDataItems($this->getConfiguration()->getDataFolders());
        $this->dm->parseDataSets($this->getConfiguration()->getDataSets());

        // Prepare Collections
        $this->cm->setConsoleOutput($this->output);
        $this->cm->parseCollections($this->getConfiguration()->getCollectionsFolders());

        // Handle PageViews
        $this->pm->setConsoleOutput($this->output);
        $this->pm->parsePageViews($this->getConfiguration()->getPageViewFolders());
        $this->pm->prepareDynamicPageViews($this->cm->getCollections());
        $this->pm->configureTwig($this->getConfiguration(), array(
            'safe'    => $this->safeMode,
            'globals' => array(
                array('name' => 'site',        'value' => $this->getConfiguration()->getConfiguration()),
                array('name' => 'collections', 'value' => $this->cm->getCollections()),
                array('name' => 'menu',        'value' => $this->pm->getSiteMenu()),
                array('name' => 'data',        'value' => $this->dm->getDataItems())
            )
        ));

        // Our output directory
        $this->outputDirectory = new Folder($this->getConfiguration()->getTargetFolder());
        $this->outputDirectory->setTargetDirectory($this->getConfiguration()->getBaseUrl());

        //
        // Theme Management
        //
        $theme = $this->configuration->getTheme();

        if (!is_null($theme))
        {
            $this->output->notice("Looking for '${theme}' theme...");

            $this->tm = new ThemeManager($theme, $this->getConfiguration()->getIncludes(), $this->getConfiguration()->getExcludes());
            $this->tm->setConsoleOutput($this->output);
            $this->tm->setTracking($tracking);
            $this->tm->setFolder($this->outputDirectory);
            $this->tm->copyFiles();
        }

        //
        // Static file management
        //
        $this->am = new AssetManager($this->getConfiguration()->getIncludes(), $this->getConfiguration()->getExcludes());
        $this->am->setConsoleOutput($this->output);
        $this->am->setFolder($this->outputDirectory);
        $this->am->setTracking($tracking);
        $this->am->copyFiles();

        //
        // Compiler
        //
        $this->output->notice('Compiling files...');
        $this->pm->compileAll(
            $this->outputDirectory
        );
    }

    public function watch ()
    {
        $this->build(true, true);

        $tracker    = new Tracker();
        $watcher    = new Watcher($tracker, $this->fs);
        $listener   = $watcher->watch(getcwd());
        $targetPath = $this->getConfiguration()->getTargetFolder();

        $this->output->notice('Watch started successfully');

        $listener->onModify(function ($resource, $path) use ($targetPath) {
            $filePath = $this->fs->getRelativePath($path);

            if ((substr($filePath, 0, strlen($targetPath)) === $targetPath) ||
                (substr($filePath, 0, 1) === '.'))
            {
                return;
            }

            $this->output->writeln(sprintf("File change detected: %s", $filePath));

            try
            {
                if ($this->pm->isPageView($filePath))
                {
                    $this->pm->compileSingle($filePath);
                }
                else if ($this->cm->isContentItem($filePath))
                {
                    $contentItem = &$this->cm->getContentItem($filePath);
                    $contentItem->refreshFileContent();

                    $this->pm->compileContentItem($contentItem);
                }
                else if ($this->tm->isFileAsset($filePath))
                {
                    $this->tm->copyFile($filePath);
                }
                else if ($this->am->isFileAsset($filePath))
                {
                    $this->am->copyFile($filePath);
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

        $this->configuration = new Configuration($configFile, $this->output);
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