<?php

/**
 * @copyright 2017 Vladimir Jimenez
 * @license   https://github.com/allejo/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx;

use allejo\stakx\Command\BuildableCommand;
use allejo\stakx\Core\StakxLogger;
use allejo\stakx\Exception\FileAwareException;
use allejo\stakx\Manager\AssetManager;
use allejo\stakx\Manager\CollectionManager;
use allejo\stakx\Manager\DataManager;
use allejo\stakx\Manager\MenuManager;
use allejo\stakx\Manager\PageManager;
use allejo\stakx\Manager\ThemeManager;
use allejo\stakx\Manager\TwigManager;
use allejo\stakx\System\FileExplorer;
use allejo\stakx\System\Filesystem;
use allejo\stakx\System\Folder;
use allejo\stakx\Twig\StakxTwigTextProfiler;
use Highlight\Highlighter;
use Kwf\FileWatcher\Event\AbstractEvent;
use Kwf\FileWatcher\Event\Create;
use Kwf\FileWatcher\Event\Modify;
use Kwf\FileWatcher\Event\Move;
use Kwf\FileWatcher\Watcher;
use Symfony\Component\Console\Output\OutputInterface;

class Website
{
    /**
     * The location of where the compiled website will be written to.
     *
     * @var Folder
     */
    private $outputDirectory;

    /**
     * The main configuration to be used to build the specified website.
     *
     * @var Configuration
     */
    private $configuration;

    /**
     * When set to true, the Stakx website will be built without a configuration file.
     *
     * @var bool
     */
    private $confLess;

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

    /** @var MenuManager */
    private $mm;

    /**
     * @var PageManager
     */
    private $pm;

    /**
     * @var ThemeManager
     */
    private $tm;

    /** @var Compiler */
    private $compiler;

    /** @var array */
    private $creationQueue;

    /**
     * Website constructor.
     *
     * @param OutputInterface $output
     */
    public function __construct(OutputInterface $output)
    {
        $this->creationQueue = array();
        $this->output = new StakxLogger($output);
        $this->cm = new CollectionManager();
        $this->dm = new DataManager();
        $this->mm = new MenuManager();
        $this->pm = new PageManager();
        $this->fs = new Filesystem();
    }

    /**
     * Compile the website.
     *
     * @param bool $tracking Whether or not to keep track of files as they're compiled to save time in 'watch'
     */
    public function build($tracking = false)
    {
        Service::setParameter(BuildableCommand::WATCHING, $tracking);

        // Configure the environment
        $this->createFolderStructure();
        $this->configureHighlighter();

        // Our output directory
        $this->outputDirectory = new Folder($this->getConfiguration()->getTargetFolder());
        $this->outputDirectory->setTargetDirectory($this->getConfiguration()->getBaseUrl());

        // Parse DataItems
        $this->dm->setLogger($this->output);
        $this->dm->parseDataItems($this->getConfiguration()->getDataFolders());
        $this->dm->parseDataSets($this->getConfiguration()->getDataSets());

        // Prepare Collections
        $this->cm->setLogger($this->output);
        $this->cm->parseCollections($this->getConfiguration()->getCollectionsFolders());

        // Handle PageViews
        $this->pm->setLogger($this->output);
        $this->pm->setCollections($this->cm->getCollections());
        $this->pm->setDatasets($this->dm->getDataItems());
        $this->pm->parsePageViews($this->getConfiguration()->getPageViewFolders());

        // Handle the site's menu
        $this->mm->setLogger($this->output);
        $this->mm->buildFromPageViews($this->pm->getStaticPageViews());

        // Configure our Twig environment
        $theme = $this->configuration->getTheme();
        $twigEnv = new TwigManager();
        $twigEnv->configureTwig($this->getConfiguration(), array(
            'safe'    => Service::getParameter(BuildableCommand::SAFE_MODE),
            'globals' => array(
                array('name' => 'site', 'value' => $this->getConfiguration()->getConfiguration()),
                array('name' => 'collections', 'value' => $this->cm->getJailedCollections()),
                array('name' => 'menu', 'value' => $this->mm->getSiteMenu()),
                array('name' => 'pages', 'value' => $this->pm->getJailedStaticPageViews()),
                array('name' => 'data', 'value' => $this->dm->getJailedDataItems()),
            ),
        ));

        $profiler = null;

        if (Service::getParameter(BuildableCommand::BUILD_PROFILE))
        {
            $profiler = new \Twig_Profiler_Profile();
            TwigManager::getInstance()->addExtension(new \Twig_Extension_Profiler($profiler));
        }

        // Compile everything
        $this->compiler = new Compiler();
        $this->compiler->setLogger($this->output);
        $this->compiler->setRedirectTemplate($this->getConfiguration()->getRedirectTemplate());
        $this->compiler->setPageViews($this->pm->getPageViews(), $this->pm->getPageViewsFlattened());
        $this->compiler->setTargetFolder($this->outputDirectory);
        $this->compiler->setThemeName($theme);
        $this->compiler->compileAll();

        if (Service::getParameter(BuildableCommand::BUILD_PROFILE))
        {
            $dumper = new StakxTwigTextProfiler();
            $dumper->setTemplateMappings($this->compiler->getTemplateMappings());
            $text = $dumper->dump($profiler);
            $this->output->writeln($text);
        }

        // At this point, we are looking at static files to copy over meaning we need to ignore all of the files that
        // make up the source of a stakx website
        $assetsToIgnore = array_merge(
            Configuration::$stakxSourceFiles,
            $this->getConfiguration()->getExcludes()
        );

        //
        // Theme Management
        //
        if (!is_null($theme))
        {
            $this->output->notice("Looking for '${theme}' theme...");

            $this->tm = new ThemeManager($theme);
            $this->tm->configureFinder($this->getConfiguration()->getIncludes(), $assetsToIgnore);
            $this->tm->setLogger($this->output);
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
        $this->am->copyFiles();
    }

    public function watch()
    {
        $this->output->writeln('Building website...');
        $this->build(true);
        $this->output->writeln(sprintf('Watching %s', getcwd()));

        $exclusions = array_merge($this->getConfiguration()->getExcludes(), array(
            $this->getConfiguration()->getTargetFolder()
        ));
        $fileExplorer = FileExplorer::create(
            getcwd(), $exclusions, $this->getConfiguration()->getIncludes()
        );

        $newWatcher = Watcher::create(getcwd());
        $newWatcher
            ->setLogger($this->output)
            ->setExcludePatterns(array_merge(
                $exclusions, FileExplorer::$vcsPatterns, array(Configuration::CACHE_FOLDER)
            ))
            ->setIterator($fileExplorer->getExplorer())
            ->addListener(Create::NAME, function ($e) { $this->watchListenerFunction($e); })
            ->addListener(Modify::NAME, function ($e) { $this->watchListenerFunction($e); })
            ->addListener(Move::NAME,   function ($e) { $this->watchListenerFunction($e); })
        ;

        $this->output->writeln('Watch started successfully');

        $newWatcher->start();
    }

    private function watchListenerFunction(AbstractEvent $event)
    {
        $filePath = $this->fs->getRelativePath($event->filename);

        try
        {
            switch ($event::getEventName())
            {
                case Create::NAME:
                    $this->creationWatcher($filePath);
                    break;

                case Modify::NAME:
                    $this->modificationWatcher($filePath);
                    break;

                case Move::NAME:
                    $newFile = $this->fs->getRelativePath($event->destFilename);

                    $this->deletionWatcher($filePath);
                    $this->creationWatcher($newFile);
                    break;
            }
        }
        catch (FileAwareException $e)
        {
            $this->output->writeln(sprintf("Your website failed to build with the following error in file '%s': %s",
                $e->getPath(),
                $e->getMessage()
            ));
        }
        catch (\Exception $e)
        {
            $this->output->writeln(sprintf('Your website failed to build with the following error: %s',
                $e->getMessage()
            ));
        }
    }

    /**
     * @return Configuration
     */
    public function getConfiguration()
    {
        return $this->configuration;
    }

    /**
     * @param string $configFile
     *
     * @throws \LogicException
     */
    public function setConfiguration($configFile)
    {
        if (!$this->fs->exists($configFile) && !$this->isConfLess())
        {
            $this->output->error('You are trying to build a website in a directory without a configuration file. Is this what you meant to do?');
            $this->output->error("To build a website without a configuration, use the '--no-conf' option");

            throw new \LogicException('Cannot build a website without a configuration when not in Configuration-less mode');
        }

        if ($this->isConfLess())
        {
            $configFile = '';
        }

        $this->configuration = new Configuration();
        $this->configuration->setLogger($this->output);
        $this->configuration->parse($configFile);

        Service::setParameter('build.preserveCase', $this->configuration->getConfiguration()['build']['preserveCase']);
    }

    /**
     * Get whether or not the website is being built in Configuration-less mode.
     *
     * @return bool True when being built with no configuration file
     */
    public function isConfLess()
    {
        return $this->confLess;
    }

    /**
     * Set whether or not the website should be built with a configuration.
     *
     * @param bool $status True when a website should be built without a configuration
     */
    public function setConfLess($status)
    {
        $this->confLess = $status;
    }

    /**
     * @param string $filePath
     */
    private function creationWatcher($filePath, $newlyCreate = true)
    {
        if ($newlyCreate)
        {
            $this->output->writeln(sprintf('File creation detected: %s', $filePath));
        }

        if ($this->pm->shouldBeTracked($filePath))
        {
            try
            {
                $this->pm->createNewItem($filePath);
                $pageView = $this->pm->refreshItem($filePath);

                $this->compiler->compilePageView($pageView);

                unset($this->creationQueue[$filePath]);
            }
            catch (\Exception $e)
            {
                $this->creationQueue[$filePath] = true;
            }
        }
        elseif ($this->cm->shouldBeTracked($filePath))
        {
            try
            {
                $contentItem = $this->cm->createNewItem($filePath);
                TwigManager::getInstance()->addGlobal('collections', $this->cm->getCollections());

                $this->pm->trackNewContentItem($contentItem);
                $this->compiler->compileContentItem($contentItem);
                $this->compiler->compileSome(array(
                    'namespace'  => 'collections',
                    'dependency' => $contentItem->getNamespace(),
                ));

                unset($this->creationQueue[$filePath]);
            }
            catch (\Exception $e)
            {
                $this->creationQueue[$filePath] = true;
            }
        }
        elseif ($this->dm->shouldBeTracked($filePath))
        {
            $change = $this->dm->createNewItem($filePath);
            TwigManager::getInstance()->addGlobal('data', $this->dm->getDataItems());

            $this->compiler->compileSome(array(
                'namespace'  => 'data',
                'dependency' => $change,
            ));
        }
        elseif (!is_null($this->tm) && $this->tm->shouldBeTracked($filePath))
        {
            $this->tm->createNewItem($filePath);
        }
        elseif ($this->am->shouldBeTracked($filePath))
        {
            $this->am->createNewItem($filePath);
        }
    }

    /**
     * @param string $filePath
     */
    private function modificationWatcher($filePath)
    {
        $this->output->writeln(sprintf('File change detected: %s', $filePath));

        if (isset($this->creationQueue[$filePath]))
        {
            $this->creationWatcher($filePath, false);
        }
        elseif ($this->compiler->isParentTemplate($filePath))
        {
            TwigManager::getInstance()->clearTemplateCache();
            $this->compiler->refreshParent($filePath);
        }
        elseif ($this->compiler->isImportDependency($filePath))
        {
            TwigManager::getInstance()->clearTemplateCache();
            $this->compiler->compileImportDependencies($filePath);
        }
        elseif ($this->pm->isTracked($filePath))
        {
            $change = $this->pm->refreshItem($filePath);

            TwigManager::getInstance()->clearTemplateCache();
            $this->compiler->compilePageView($change);
        }
        elseif ($this->cm->isTracked($filePath))
        {
            $contentItem = &$this->cm->getContentItem($filePath);
            $contentItem->refreshFileContent();

            $this->compiler->compileContentItem($contentItem);
            $this->compiler->compileSome(array(
                'namespace'  => 'collections',
                'dependency' => $contentItem->getNamespace(),
            ));
        }
        elseif ($this->dm->isTracked($filePath))
        {
            $change = $this->dm->refreshItem($filePath);
            TwigManager::getInstance()->addGlobal('data', $this->dm->getDataItems());

            $this->compiler->compileSome(array(
                'namespace'  => 'data',
                'dependency' => $change,
            ));
        }
        elseif (!is_null($this->tm) && $this->tm->isTracked($filePath))
        {
            $this->tm->refreshItem($filePath);
        }
        elseif ($this->am->isTracked($filePath))
        {
            $this->am->refreshItem($filePath);
        }
    }

    /**
     * @param string $filePath
     */
    private function deletionWatcher($filePath)
    {
    }

    /**
     * Prepare the Stakx environment by creating necessary cache folders.
     */
    private function createFolderStructure()
    {
        $targetDir = $this->fs->absolutePath($this->configuration->getTargetFolder());

        if (!Service::getParameter(BuildableCommand::NO_CLEAN))
        {
            $this->fs->remove($targetDir);
        }

        if (!Service::getParameter(BuildableCommand::USE_CACHE))
        {
            $this->fs->remove($this->fs->absolutePath(Configuration::CACHE_FOLDER));
            $this->fs->mkdir($this->fs->absolutePath($this->fs->appendPath(Configuration::CACHE_FOLDER, 'twig')));
        }

        $this->fs->mkdir($targetDir);
    }

    private function configureHighlighter()
    {
        // Configure our highlighter
        Service::setParameter(Configuration::HIGHLIGHTER_ENABLED, $this->getConfiguration()->isHighlighterEnabled());

        if (Service::getParameter(Configuration::HIGHLIGHTER_ENABLED))
        {
            foreach ($this->getConfiguration()->getHighlighterCustomLanguages() as $lang => $path)
            {
                $fullPath = $this->fs->absolutePath($path);

                if (!$this->fs->exists($fullPath))
                {
                    $this->output->warning('The following language definition could not be found: {lang}', array(
                        'lang' => $path
                    ));
                    continue;
                }

                Highlighter::registerLanguage($lang, $fullPath);
                $this->output->debug('Loading custom language {lang} from {path}...', array(
                    'lang' => $lang,
                    'path' => $path
                ));
            }
        }
    }
}
