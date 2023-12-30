<?php

/**
 * @copyright 2018 Vladimir Jimenez
 * @license   https://github.com/stakx-io/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\EventSubscriber;

use __\__;
use allejo\stakx\AssetEngine\AssetEngineInterface;
use allejo\stakx\AssetEngine\AssetEngineManager;
use allejo\stakx\Configuration;
use allejo\stakx\Document\StaticPageView;
use allejo\stakx\Event\CompilerPostRenderStaticPageView;
use allejo\stakx\Event\ConfigurationParseComplete;
use allejo\stakx\Event\PageManagerPostProcess;
use allejo\stakx\Filesystem\FileExplorer;
use allejo\stakx\Filesystem\FilesystemLoader as fs;
use allejo\stakx\Filesystem\FilesystemPath;
use allejo\stakx\Filesystem\WritableFolder;
use allejo\stakx\RuntimeStatus;
use allejo\stakx\Service;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class AssetEngineSubscriber implements EventSubscriberInterface
{
    private array $assetPageViews = [];

    public function __construct(
        private readonly AssetEngineManager $assetEngineManager,
        private readonly LoggerInterface $logger
    ) {
    }

    public function processConfigurationSettings(ConfigurationParseComplete $event): void
    {
        $configuration = $event->getConfiguration()->getConfiguration();

        foreach ($this->assetEngineManager->getEngines() as $engine)
        {
            $defaults = __::get($configuration, $engine->getConfigurationNamespace(), []);
            $options = array_merge($engine->getDefaultConfiguration(), $defaults);

            $engine->setOptions($options);
        }
    }

    public function processAssetEnginePageView(PageManagerPostProcess $event): void
    {
        /**
         * @var string               $folder
         * @var AssetEngineInterface $engine
         */
        foreach ($this->assetEngineManager->getFoldersToWatch() as $folder => $engine)
        {
            $assetFolder = fs::absolutePath($folder);

            if (!fs::exists($assetFolder))
            {
                continue;
            }

            $engine->setPageManager($event->getPageManager());
            $extensions = [];

            foreach ($engine->getExtensions() as $extension)
            {
                $extensions[] = "/.{$extension}.twig$/";
            }

            $explorer = FileExplorer::create($assetFolder, $extensions, [], FileExplorer::INCLUDE_ONLY_FILES | FileExplorer::IGNORE_DIRECTORIES);

            foreach ($explorer as $file)
            {
                $assetPageView = new StaticPageView($file);

                try
                {
                    $event->getPageManager()->trackNewPageView($assetPageView);
                    $this->assetPageViews[$assetPageView->getRelativeFilePath()] = [
                        'engine' => $engine,
                    ];
                }
                catch (\Exception $e)
                {
                    $this->logger->error('An exception occurred while creating a Static PageView for an AssetEngine');
                    $this->logger->error('  {message}', [
                        'message' => $e->getMessage(),
                    ]);
                }
            }
        }
    }

    public function compileAssetEnginePageViews(CompilerPostRenderStaticPageView $event): void
    {
        $pageView = $event->getPageView();
        $filePath = $pageView->getRelativeFilePath();

        if (isset($this->assetPageViews[$filePath]))
        {
            /** @var AssetEngineInterface $engine */
            $engine = $this->assetPageViews[$filePath]['engine'];
            $cacheDir = $this->buildCacheFolder($engine);

            if (Service::hasRunTimeFlag(RuntimeStatus::USING_CACHE))
            {
                $engine->loadCache($cacheDir);
            }

            $output = $engine->parse($event->getCompiledOutput(), [
                'pageview' => $pageView,
            ]);

            $engine->saveCache($cacheDir);
            $event->setCompiledOutput($output);
        }
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ConfigurationParseComplete::NAME => 'processConfigurationSettings',
            PageManagerPostProcess::NAME => 'processAssetEnginePageView',
            CompilerPostRenderStaticPageView::NAME => 'compileAssetEnginePageViews',
        ];
    }

    private function buildCacheFolder(AssetEngineInterface $engine): WritableFolder
    {
        $cacheDirPath = new FilesystemPath(Service::getWorkingDirectory() . '/');
        $cacheDirPath
            ->appendToPath(Configuration::CACHE_FOLDER . '/')
            ->appendToPath(__::slug(strtolower($engine->getName())) . '/')
        ;

        fs::mkdir($cacheDirPath);

        $cacheDir = new WritableFolder($cacheDirPath);
        $cacheDir->freeze();

        return $cacheDir;
    }
}
