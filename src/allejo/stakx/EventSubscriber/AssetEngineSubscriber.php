<?php

/**
 * @copyright 2018 Vladimir Jimenez
 * @license   https://github.com/stakx-io/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\EventSubscriber;

use allejo\stakx\AssetEngine\AssetEngine;
use allejo\stakx\AssetEngine\AssetEngineManager;
use allejo\stakx\Document\StaticPageView;
use allejo\stakx\Event\PageManagerPostProcess;
use allejo\stakx\Filesystem\FileExplorer;
use allejo\stakx\Filesystem\FilesystemLoader as fs;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class AssetEngineSubscriber implements EventSubscriberInterface
{
    private $assetEngineManager;

    public function __construct(AssetEngineManager $assetEngineManager)
    {
        $this->assetEngineManager = $assetEngineManager;
    }

    public function processAssetEnginePageView(PageManagerPostProcess $event)
    {
        /**
         * @var string      $folder
         * @var AssetEngine $engine
         */
        foreach ($this->assetEngineManager->getFoldersToWatch() as $folder => $engine)
        {
            $assetFolder = fs::absolutePath($folder);

            if (!fs::exists($assetFolder))
            {
                continue;
            }

            $extensions = [];

            foreach ($engine->getExtensions() as $extension)
            {
                $extensions[] = "/.{$extension}.twig$/";
            }

            $explorer = FileExplorer::create($assetFolder, [], $extensions, FileExplorer::IGNORE_DIRECTORIES);

            foreach ($explorer as $file)
            {
                $assetPageView = new StaticPageView($file);
                $compiled = $engine->parse($assetPageView->getContent());
                $assetPageView->setContent($compiled);

                $event->getPageManager()->trackNewPageView($assetPageView);
            }
        }
    }

    public static function getSubscribedEvents()
    {
        return [
            PageManagerPostProcess::NAME => 'processAssetEnginePageView',
        ];
    }
}
