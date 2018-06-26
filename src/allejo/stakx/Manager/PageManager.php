<?php

/**
 * @copyright 2018 Vladimir Jimenez
 * @license   https://github.com/stakx-io/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Manager;

use allejo\stakx\AssetEngine\AssetEngine;
use allejo\stakx\AssetEngine\AssetEngineManager;
use allejo\stakx\Configuration;
use allejo\stakx\Document\BasePageView;
use allejo\stakx\Document\ContentItem;
use allejo\stakx\Document\DataItem;
use allejo\stakx\Document\DynamicPageView;
use allejo\stakx\Document\JailedDocument;
use allejo\stakx\Document\RepeaterPageView;
use allejo\stakx\Document\StaticPageView;
use allejo\stakx\Event\PageViewAdded;
use allejo\stakx\Event\PageViewDefinitionAdded;
use allejo\stakx\Exception\CollectionNotFoundException;
use allejo\stakx\Filesystem\File;
use allejo\stakx\Filesystem\FileExplorer;
use allejo\stakx\Filesystem\FilesystemLoader as fs;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * This class is responsible for handling all of the PageViews within a website.
 *
 * PageManager will parse all available dynamic and static PageViews. After, dynamic PageViews will be prepared by
 * setting the appropriate values for each ContentItem such as permalinks.
 */
class PageManager extends TrackingManager
{
    /** @var StaticPageView[] A place to store a reference to static PageViews with titles. */
    private $staticPages;
    private $configuration;
    private $assetEngineManager;
    private $collectionManager;
    private $dataManager;
    private $eventDispatcher;
    private $logger;

    /**
     * PageManager constructor.
     */
    public function __construct(
        Configuration $configuration,
        AssetEngineManager $assetEngineManager,
        CollectionManager $collectionManager,
        DataManager $dataManager,
        EventDispatcherInterface $eventDispatcher,
        LoggerInterface $logger
    ) {
        $this->trackedItems = [
            BasePageView::STATIC_TYPE => [],
            BasePageView::DYNAMIC_TYPE => [],
            BasePageView::REPEATER_TYPE => [],
        ];
        $this->staticPages = [];
        $this->configuration = $configuration;
        $this->assetEngineManager = $assetEngineManager;
        $this->collectionManager = $collectionManager;
        $this->dataManager = $dataManager;
        $this->eventDispatcher = $eventDispatcher;
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function compileManager()
    {
        $this->parseAssetPageViews();
        $this->parsePageViews($this->configuration->getPageViewFolders());
    }

    public function parseAssetPageViews()
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

                $this->handleTrackableStaticPageView($assetPageView);
                $this->addObjectToTracker($assetPageView, $assetPageView->getType());
            }
        }
    }

    /**
     * Go through all of the PageView directories and create a respective PageView for each and classify them by type.
     *
     * @param string[] $pageViewFolders
     *
     * @since 0.1.0
     */
    public function parsePageViews(array $pageViewFolders)
    {
        foreach ($pageViewFolders as $pageViewFolderName)
        {
            $pageViewFolderPath = fs::absolutePath($pageViewFolderName);

            if (!fs::exists($pageViewFolderPath))
            {
                $this->logger->warning("The '$pageViewFolderName' folder could not be found");
                continue;
            }

            $event = new PageViewDefinitionAdded($pageViewFolderName);
            $this->eventDispatcher->dispatch(PageViewDefinitionAdded::NAME, $event);

            $this->scanTrackableItems($pageViewFolderPath, [
                'fileExplorer' => FileExplorer::INCLUDE_ONLY_FILES,
            ], ['/.html$/', '/.twig$/']);
        }
    }

    /**
     * Get all of the PageViews in an associative array with PageView types as the keys.
     *
     * @since  0.1.1
     *
     * @return BasePageView[][]
     */
    public function &getPageViews()
    {
        return $this->trackedItems;
    }

    /**
     * Get all of the PageViews in flat array.
     *
     * @since  0.1.1
     *
     * @return BasePageView[]
     */
    public function &getPageViewsFlattened()
    {
        return $this->trackedItemsFlattened;
    }

    /**
     * Get the static PageViews tracked by this manager indexed by their title.
     *
     * @since 0.1.0
     *
     * @return StaticPageView[]
     */
    public function getStaticPageViews()
    {
        return $this->staticPages;
    }

    /**
     * Get the jailed version of the static PageViews indexed by their title.
     *
     * @since 0.1.0
     *
     * @return JailedDocument[]
     */
    public function getJailedStaticPageViews()
    {
        $jailedObjects = [];

        foreach ($this->staticPages as $key => $value)
        {
            $jailedObjects[$key] = $value->createJail();
        }

        return $jailedObjects;
    }

    /**
     * Add a new ContentItem to the respective parent PageView of the ContentItem.
     *
     * @param ContentItem $contentItem
     *
     * @since 0.1.0
     */
    public function trackNewContentItem(&$contentItem)
    {
        $collection = $contentItem->getNamespace();
        $this->trackedItems[BasePageView::DYNAMIC_TYPE][$collection]->addCollectableItem($contentItem);
    }

    /**
     * {@inheritdoc}
     */
    protected function &handleTrackableItem(File $filePath, array $options = [])
    {
        $pageView = BasePageView::create($filePath, [
            'site' => $this->configuration->getConfiguration(),
        ]);
        $namespace = $pageView->getType();

        switch ($namespace)
        {
            case BasePageView::STATIC_TYPE:
                $this->handleTrackableStaticPageView($pageView);
                break;

            case BasePageView::DYNAMIC_TYPE:
                $this->handleTrackableDynamicPageView($pageView);
                break;

            case BasePageView::REPEATER_TYPE:
                $this->handleTrackableRepeaterPageView($pageView);
                break;

            default:
                break;
        }

        $event = new PageViewAdded($pageView);
        $this->eventDispatcher->dispatch(PageViewAdded::NAME, $event);

        $this->addObjectToTracker($pageView, $namespace);

        return $pageView;
    }

    /**
     * Handle special behavior and treatment for static PageViews while we're iterating through them.
     *
     * @param StaticPageView $pageView
     *
     * @since 0.1.0
     */
    private function handleTrackableStaticPageView(&$pageView)
    {
        $pageView->evaluateFrontMatter([], [
            'site' => $this->configuration->getConfiguration(),
        ]);

        if (empty($pageView['title']))
        {
            return;
        }

        $this->staticPages[$pageView['title']] = &$pageView;
    }

    /**
     * Handle special behavior and treatment for dynamic PageViews while we're iterating through them.
     *
     * @param DynamicPageView $pageView
     *
     * @since 0.1.0
     *
     * @throws \Exception
     */
    private function handleTrackableDynamicPageView(&$pageView)
    {
        $frontMatter = $pageView->getRawFrontMatter();
        $dataSource = null;
        $namespace = null;

        if (isset($frontMatter['collection']))
        {
            $dataSource = &$this->collectionManager->getCollections();
            $namespace = 'collection';
        }
        elseif (isset($frontMatter['dataset']))
        {
            $dataSource = &$this->dataManager->getDataItems();
            $namespace = 'dataset';
        }

        if ($dataSource === null)
        {
            throw new \Exception('Invalid Dynamic PageView defined');
        }

        $collection = $frontMatter[$namespace];

        if (!isset($dataSource[$collection]))
        {
            throw new CollectionNotFoundException("The '$collection' $namespace is not defined");
        }

        /** @var ContentItem|DataItem $item */
        foreach ($dataSource[$collection] as &$item)
        {
            $item->evaluateFrontMatter($frontMatter, [
                'site' => $this->configuration->getConfiguration(),
            ]);
            $item->saveParentPageView($pageView);
            $item->buildPermalink(true);

            $pageView->addCollectableItem($item);
        }
    }

    /**
     * Handle special behavior and treatment for repeater PageViews while we're iterating through them.
     *
     * @param RepeaterPageView $pageView
     *
     * @since 0.2.0
     */
    private function handleTrackableRepeaterPageView(&$pageView)
    {
        $pageView->evaluateFrontMatter([], [
            'site' => $this->configuration->getConfiguration(),
        ]);
        $pageView->configurePermalinks();
    }
}
