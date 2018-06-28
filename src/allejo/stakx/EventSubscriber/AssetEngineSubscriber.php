<?php

/**
 * @copyright 2018 Vladimir Jimenez
 * @license   https://github.com/stakx-io/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\EventSubscriber;

use __\__;
use allejo\stakx\AssetEngine\AssetEngineInterface;
use allejo\stakx\AssetEngine\AssetEngineManager;
use allejo\stakx\Document\StaticPageView;
use allejo\stakx\Event\ConfigurationParseComplete;
use allejo\stakx\Event\PageManagerPostProcess;
use allejo\stakx\Filesystem\FileExplorer;
use allejo\stakx\Filesystem\FilesystemLoader as fs;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class AssetEngineSubscriber implements EventSubscriberInterface
{
    private $assetEngineManager;
    private $logger;

    public function __construct(AssetEngineManager $assetEngineManager, LoggerInterface $logger)
    {
        $this->assetEngineManager = $assetEngineManager;
        $this->logger = $logger;
    }

    public function processConfigurationSettings(ConfigurationParseComplete $event)
    {
        $configuration = $event->getConfiguration()->getConfiguration();

        /** @var AssetEngineInterface $engine */
        foreach ($this->assetEngineManager->getEngines() as $engine)
        {
            $defaults = __::get($configuration, $engine->getConfigurationNamespace(), []);
            $options = array_merge($engine->getDefaultConfiguration(), $defaults);

            $engine->setOptions($options);
        }
    }

    public function processAssetEnginePageView(PageManagerPostProcess $event)
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

            $explorer = FileExplorer::create($assetFolder, [], $extensions, FileExplorer::IGNORE_DIRECTORIES);

            foreach ($explorer as $file)
            {
                $assetPageView = new StaticPageView($file);

                try
                {
                    $event->getPageManager()->trackNewPageView($assetPageView);

                    $compiledSass = $engine->parse($assetPageView->getContent(), [
                        'pageview' => $assetPageView,
                    ]);
                    $assetPageView->setContent($compiledSass);
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

    public static function getSubscribedEvents()
    {
        return [
            ConfigurationParseComplete::NAME => 'processConfigurationSettings',
            PageManagerPostProcess::NAME => 'processAssetEnginePageView',
        ];
    }
}
