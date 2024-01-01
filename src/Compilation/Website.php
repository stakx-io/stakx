<?php

/**
 * @copyright 2018 Vladimir Jimenez
 * @license   https://github.com/stakx-io/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Compilation;

use allejo\stakx\Compilation\Compiler;
use allejo\stakx\Compilation\Configuration;
use allejo\stakx\Event\BuildProcessComplete;
use allejo\stakx\Filesystem\FilesystemLoader as fs;
use allejo\stakx\Filesystem\WritableFolder;
use allejo\stakx\Manager\AssetManager;
use allejo\stakx\Manager\ThemeManager;
use allejo\stakx\RuntimeStatus;
use allejo\stakx\Service;
use allejo\stakx\Templating\TemplateBridgeInterface;
use Highlight\Highlighter;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class Website
{
    private $eventDispatcher;
    private $templateBridge;
    private $configuration;
    private $assetManager;
    private $compiler;
    private $logger;

    public function __construct(
        Compiler $compiler,
        Configuration $configuration,
        AssetManager $assetManager,
        TemplateBridgeInterface $templateBridge,
        EventDispatcherInterface $eventDispatcher,
        LoggerInterface $logger
    ) {
        $this->eventDispatcher = $eventDispatcher;
        $this->templateBridge = $templateBridge;
        $this->configuration = $configuration;
        $this->assetManager = $assetManager;
        $this->compiler = $compiler;
        $this->logger = $logger;

        Service::setOption('theme', $this->getConfiguration()->getTheme());

        $this->configureHighlighter();
    }

    public function getCompiler()
    {
        return $this->compiler;
    }

    /**
     * Compile the website.
     *
     * @return true if the website built successfully
     */
    public function build()
    {
        if (empty($this->getConfiguration()->getPageViewFolders()))
        {
            $this->logger->error('No PageViews were configured for this site. Check the `pageviews` key in your _config.yml.');

            return false;
        }

        // Configure the environment
        $this->createFolderStructure();

        // Our output directory
        $outputDirectory = new WritableFolder($this->getConfiguration()->getTargetFolder());
        $outputDirectory->setTargetDirectory($this->getConfiguration()->getBaseUrl());

        // Compile everything
        $theme = $this->getConfiguration()->getTheme();

        $this->compiler->setTargetFolder($outputDirectory);
        $this->compiler->setThemeName($theme);
        $this->compiler->compileAll();

        if (Service::hasRunTimeFlag(RuntimeStatus::IN_PROFILE_MODE))
        {
            if (!$this->templateBridge->hasProfiler())
            {
                $this->logger->writeln('This template engine currently does not support a profiler.');
            }
            else
            {
                $profilerText = $this->templateBridge->getProfilerOutput($this->compiler);
                $this->logger->writeln($profilerText);
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
        if ($theme !== null)
        {
            $this->logger->notice("Looking for '{$theme}' theme...");

            $tm = new ThemeManager($theme, $this->eventDispatcher, $this->logger);
            $tm->configureFinder($this->getConfiguration()->getIncludes(), $assetsToIgnore);
            $tm->setFolder($outputDirectory);
            $tm->copyFiles();
        }

        //
        // Static file management
        //
        $this->assetManager->configureFinder($this->getConfiguration()->getIncludes(), $assetsToIgnore);
        $this->assetManager->setFolder($outputDirectory);
        $this->assetManager->copyFiles();

        $this->eventDispatcher->dispatch(BuildProcessComplete::NAME, new BuildProcessComplete());
    }

    /**
     * @return Configuration
     */
    public function getConfiguration()
    {
        return $this->configuration;
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

        if ($this->getConfiguration()->isHighlighterUsingLineNumbers()) {
            Service::setRuntimeFlag(RuntimeStatus::USING_LINE_NUMBERS);
        }

        foreach ($this->getConfiguration()->getHighlighterCustomLanguages() as $lang => $path)
        {
            $fullPath = fs::absolutePath($path);

            if (!fs::exists($fullPath))
            {
                $this->logger->warning('The following language definition could not be found: {lang}', [
                    'lang' => $path,
                ]);
                continue;
            }

            Highlighter::registerLanguage($lang, $fullPath);
            $this->logger->debug('Loading custom language {lang} from {path}...', [
                'lang' => $lang,
                'path' => $path,
            ]);
        }
    }
}
