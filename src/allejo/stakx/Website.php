<?php

/**
 * @copyright 2018 Vladimir Jimenez
 * @license   https://github.com/stakx-io/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx;

use allejo\stakx\Event\BuildProcessComplete;
use allejo\stakx\Filesystem\FilesystemLoader as fs;
use allejo\stakx\Filesystem\Folder;
use allejo\stakx\Manager\AssetManager;
use allejo\stakx\Manager\ThemeManager;
use Highlight\Highlighter;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

class Website implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    /**
     * Compile the website.
     *
     * @return true if the website built successfully
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
        $outputDirectory = new Folder($this->getConfiguration()->getTargetFolder());
        $outputDirectory->setTargetDirectory($this->getConfiguration()->getBaseUrl());

        $templateEngine = $this->container->get('templating');

        // Compile everything
        $theme = $this->getConfiguration()->getTheme();

        $compiler = $this->container->get('compiler');
        $compiler->setTargetFolder($outputDirectory);
        $compiler->setThemeName($theme);
        $compiler->compileAll();

        if (Service::hasRunTimeFlag(RuntimeStatus::IN_PROFILE_MODE))
        {
            if (!$templateEngine->hasProfiler())
            {
                $logger->writeln('This template engine currently does not support a profiler.');
            }
            else
            {
                $profilerText = $templateEngine->getProfilerOutput($compiler);
                $logger->writeln($profilerText);
            }
        }

        // At this point, we are looking at static files to copy over meaning we need to ignore all of the files that
        // make up the source of a stakx website
        $assetsToIgnore = array_merge(
            Configuration::$stakxSourceFiles,
            $this->getConfiguration()->getExcludes()
        );

        $eventDispatcher = $this->container->get('event_dispatcher');

        //
        // Theme Management
        //
        if ($theme !== null)
        {
            $logger->notice("Looking for '${theme}' theme...");

            $tm = new ThemeManager($theme, $eventDispatcher, $logger);
            $tm->configureFinder($this->getConfiguration()->getIncludes(), $assetsToIgnore);
            $tm->setFolder($outputDirectory);
            $tm->copyFiles();
        }

        //
        // Static file management
        //
        $am = $this->container->get(AssetManager::class);
        $am->configureFinder($this->getConfiguration()->getIncludes(), $assetsToIgnore);
        $am->setFolder($outputDirectory);
        $am->copyFiles();

        $eventDispatcher->dispatch(BuildProcessComplete::NAME, new BuildProcessComplete());
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

        $logger = $this->container->get('logger');

        Service::setRuntimeFlag(RuntimeStatus::USING_HIGHLIGHTER);

        foreach ($this->getConfiguration()->getHighlighterCustomLanguages() as $lang => $path)
        {
            $fullPath = fs::absolutePath($path);

            if (!fs::exists($fullPath))
            {
                $logger->warning('The following language definition could not be found: {lang}', [
                    'lang' => $path,
                ]);
                continue;
            }

            Highlighter::registerLanguage($lang, $fullPath);
            $logger->debug('Loading custom language {lang} from {path}...', [
                'lang' => $lang,
                'path' => $path,
            ]);
        }
    }
}
