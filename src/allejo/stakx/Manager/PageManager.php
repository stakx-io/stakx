<?php

/**
 * @copyright 2017 Vladimir Jimenez
 * @license   https://github.com/allejo/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Manager;

use allejo\stakx\Exception\CollectionNotFoundException;
use allejo\stakx\Object\ContentItem;
use allejo\stakx\Object\DynamicPageView;
use allejo\stakx\Object\JailObject;
use allejo\stakx\Object\PageView;
use allejo\stakx\System\FileExplorer;

/**
 * This class is responsible for handling all of the PageViews within a website.
 *
 * PageManager will parse all available dynamic and static PageViews. After, dynamic PageViews will be prepared by
 * setting the appropriate values for each ContentItem such as permalinks.
 *
 * @internal
 */
class PageManager extends TrackingManager
{
    /**
     * A reference to the collections available to this website
     *
     * @var ContentItem[][]
     */
    private $collections;

    /**
     * A place to store a reference to static PageViews with titles
     *
     * @var PageView[]
     */
    private $staticPages;

    /**
     * PageManager constructor
     */
    public function __construct()
    {
        parent::__construct();

        $this->collections = array();
        $this->staticPages = array();
    }

    /**
     * Give this manager the collections we'll be using for dynamic PageViews
     *
     * @param ContentItem[][] $collections
     * @since 0.1.0
     */
    public function setCollections (&$collections)
    {
        $this->collections = &$collections;
    }

    /**
     * Get all of the PageViews tracked by this manager
     *
     * @since 0.1.0
     * @return PageView[][]
     */
    public function getAllPageViews ()
    {
        return $this->trackedItemsFlattened;
    }

    /**
     * Get the static PageViews tracked by this manager indexed by their title
     *
     * @since 0.1.0
     * @return PageView[]
     */
    public function getStaticPageViews ()
    {
        return $this->staticPages;
    }

    /**
     * Get the jailed version of the static PageViews indexed by their title
     *
     * @since 0.1.0
     * @return JailObject[]
     */
    public function getJailedStaticPageViews ()
    {
        $jailedObjects = array();

        foreach ($this->staticPages as $key => $value)
        {
            $jailedObjects[$key] = $value->createJail();
        }

        return $jailedObjects;
    }

    /**
     * Go through all of the PageView directories and create a respective PageView for each and classify them as a
     * dynamic or static PageView.
     *
     * @param string[] $pageViewFolders
     * @since 0.1.0
     */
    public function parsePageViews ($pageViewFolders)
    {
        if (empty($pageViewFolders)) { return; }

        foreach ($pageViewFolders as $pageViewFolderName)
        {
            /** @var string $pageViewFolderPath */
            $pageViewFolderPath = $this->fs->absolutePath($pageViewFolderName);

            if (!$this->fs->exists($pageViewFolderPath))
            {
                $this->output->warning("The '$pageViewFolderName' folder could not be found");
                continue;
            }

            $this->scanTrackableItems($pageViewFolderPath, array(
                'fileExplorer' => FileExplorer::INCLUDE_ONLY_FILES
            ), array('/.html$/', '/.twig$/'));
        }
    }

    /**
     * Add a new ContentItem to the respective parent PageView of the ContentItem
     *
     * @param ContentItem $contentItem
     * @since 0.1.0
     */
    public function trackNewContentItem (&$contentItem)
    {
        $collection = $contentItem->getCollection();
        $this->trackedItems[PageView::DYNAMIC_TYPE][$collection]->addContentItem($contentItem);
    }

    /**
     * {@inheritdoc}
     */
    protected function handleTrackableItem ($filePath, $options = array())
    {
        $pageView  = PageView::create($filePath);
        $namespace = $pageView->getType();
        $storageKey = $pageView->getRelativeFilePath();

        switch ($namespace)
        {
            case PageView::STATIC_TYPE:
                $this->handleTrackableStaticPageView($pageView);
                break;

            case PageView::DYNAMIC_TYPE:
                $this->handleTrackableDynamicPageView($pageView);
                $storageKey = $pageView->getCollection();
                break;

            default:
                break;
        }

        $this->addObjectToTracker($pageView, $storageKey, $namespace);
    }

    /**
     * Handle special behavior and treatment for static PageViews while we're iterating through them
     *
     * @param PageView $pageView
     * @since 0.1.0
     */
    private function handleTrackableStaticPageView (&$pageView)
    {
        if (empty($pageView['title'])) { return; }

        $this->staticPages[$pageView['title']] = &$pageView;
    }

    /**
     * Handle special behavior and treatment for dynamic PageViews while we're iterating through them
     *
     * @param DynamicPageView $pageView
     * @since 0.1.0
     */
    private function handleTrackableDynamicPageView (&$pageView)
    {
        $frontMatter = $pageView->getFrontMatter(false);
        $collection = $frontMatter['collection'];

        if (!isset($this->collections[$collection]))
        {
            throw new CollectionNotFoundException("The '$collection' collection is not defined");
        }

        foreach ($this->collections[$collection] as &$item)
        {
            $item->evaluateFrontMatter($frontMatter);
            $pageView->addContentItem($item);
        }
    }
}