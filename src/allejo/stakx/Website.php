<?php

/**
 * @copyright 2018 Vladimir Jimenez
 * @license   https://github.com/stakx-io/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx;

use allejo\stakx\Command\BuildCommand;
use allejo\stakx\Logger;
use allejo\stakx\Event\BuildProcessComplete;
use allejo\stakx\Exception\FileAwareException;
use allejo\stakx\Filesystem\FileExplorer;
use allejo\stakx\Filesystem\FilesystemLoader as fs;
use allejo\stakx\Filesystem\Folder;
use allejo\stakx\Manager\AssetManager;
use allejo\stakx\Manager\CollectionManager;
use allejo\stakx\Manager\DataManager;
use allejo\stakx\Manager\PageManager;
use allejo\stakx\Manager\ThemeManager;
use allejo\stakx\Manager\TwigManager;
use Highlight\Highlighter;
use Kwf\FileWatcher\Event\AbstractEvent;
use Kwf\FileWatcher\Event\Create;
use Kwf\FileWatcher\Event\Modify;
use Kwf\FileWatcher\Event\Move;
use Kwf\FileWatcher\Watcher;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;

class Website
{
    /**
     * The location of where the compiled website will be written to.
     *
     * @var Folder
     */
    private $outputDirectory;

    /**
     * @var Logger
     */
    private $output;

    /**
     * @var AssetManager
     */
    private $am;

    /**
     * @var ThemeManager
     */
    private $tm;

    /** @var Compiler */
    private $compiler;

    private $container;

    /**
     * Constructor.
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->output = $container->get('logger');
    }

    /**
     * Compile the website.
     *
     * @return True if the website built successfully.
     */
    public function build()
    {
        $logger = $this->container->get('logger');
        $conf = $this->container->get(Configuration::class);

        if (empty($conf->getPageViewFolders()))
        {
            $logger->error('No PageViews were configured for this site. Check the `pageviews` key in your _config.yml.');

            return false;
        }

        // Configure the environment
        $this->createFolderStructure();
        $this->configureHighlighter();

        // Our output directory
        $this->outputDirectory = new Folder($this->getConfiguration()->getTargetFolder());
        $this->outputDirectory->setTargetDirectory($this->getConfiguration()->getBaseUrl());

        $templateEngine = $this->container->get('templating');

        // Compile everything
        $theme = $this->getConfiguration()->getTheme();

        $this->compiler = $this->container->get('compiler');
        $this->compiler->setTargetFolder($this->outputDirectory);
        $this->compiler->setThemeName($theme);
        $this->compiler->compileAll();

        if (Service::hasRunTimeFlag(RuntimeStatus::IN_PROFILE_MODE))
        {
            if (!$templateEngine->hasProfiler())
            {
                $logger->writeln('This template engine currently does not support a profiler.');
            }
            else
            {
                $profilerText = $templateEngine->getProfilerOutput($this->compiler);
                $logger->writeln($profilerText);
            }
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
            $logger->notice("Looking for '${theme}' theme...");

            $this->tm = new ThemeManager($theme, $this->container->get('event_dispatcher'), $this->container->get('logger'));
            $this->tm->configureFinder($this->getConfiguration()->getIncludes(), $assetsToIgnore);
            $this->tm->setFolder($this->outputDirectory);
            $this->tm->copyFiles();
        }

        //
        // Static file management
        //
        $this->am = $this->container->get(AssetManager::class);
        $this->am->configureFinder($this->getConfiguration()->getIncludes(), $assetsToIgnore);
        $this->am->setFolder($this->outputDirectory);
        $this->am->copyFiles();

        /** @var EventDispatcher $dispatcher */
        $dispatcher = $this->container->get('event_dispatcher');
        $dispatcher->dispatch(BuildProcessComplete::NAME, new BuildProcessComplete());
    }

    /**
     * @return Configuration
     */
    public function getConfiguration()
    {
        return $this->container->get(Configuration::class);
    }

    /**
     * Prepare the Stakx environment by creating necessary cache folders.
     */
    private function createFolderStructure()
    {
        $targetDir = fs::absolutePath($this->getConfiguration()->getTargetFolder());

        if (!Service::hasRunTimeFlag(RuntimeStatus::BOOT_WITHOUT_CLEAN))
        {
            fs::remove($targetDir);
        }

        if (!Service::hasRunTimeFlag(RuntimeStatus::USING_CACHE))
        {
            fs::remove(fs::absolutePath(Configuration::CACHE_FOLDER, 'twig'));
            fs::mkdir(fs::absolutePath(fs::appendPath(Configuration::CACHE_FOLDER, 'twig')));
        }

        fs::mkdir($targetDir);
    }

    /**
     * Configure the Highlighter object for highlighting code blocks.
     */
    private function configureHighlighter()
    {
        $enabled = $this->getConfiguration()->isHighlighterEnabled();

        if (!$enabled)
        {
            return;
        }

        Service::setRuntimeFlag(RuntimeStatus::USING_HIGHLIGHTER);

        foreach ($this->getConfiguration()->getHighlighterCustomLanguages() as $lang => $path)
        {
            $fullPath = fs::absolutePath($path);

            if (!fs::exists($fullPath))
            {
                $this->output->warning('The following language definition could not be found: {lang}', [
                    'lang' => $path,
                ]);
                continue;
            }

            Highlighter::registerLanguage($lang, $fullPath);
            $this->output->debug('Loading custom language {lang} from {path}...', [
                'lang' => $lang,
                'path' => $path,
            ]);
        }
    }
}
