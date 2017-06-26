<?php

/**
 * @copyright 2017 Vladimir Jimenez
 * @license   https://github.com/allejo/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Manager;

use allejo\stakx\Document\ContentItem;
use allejo\stakx\Document\DataItem;
use allejo\stakx\Document\DynamicPageView;
use allejo\stakx\Document\JailedDocument;
use allejo\stakx\Document\PageView;
use allejo\stakx\Exception\CollectionNotFoundException;
use allejo\stakx\Exception\DataSetNotFoundException;
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
     * A reference to the collections available to this website.
     *
     * @var ContentItem[][]
     */
    private $collections;

    /**
     * A place to store a reference to static PageViews with titles.
     *
     * @var PageView[]
     */
    private $staticPages;

    /**
     * @var DataItem[]|array
     */
    private $datasets;

    /**
     * PageManager constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->trackedItems = array(
            PageView::STATIC_TYPE   => array(),
            PageView::DYNAMIC_TYPE  => array(),
            PageView::REPEATER_TYPE => array(),
        );
        $this->collections = array();
        $this->staticPages = array();
    }

    /**
     * Give this manager the collections we'll be using for dynamic PageViews.
     *
     * @param ContentItem[][] $collections
     *
     * @since 0.1.0
     */
    public function setCollections(&$collections)
    {
        $this->collections = &$collections;
    }

    public function setDatasets($datasets)
    {
        $this->datasets = $datasets;
    }

    /**
     * Get all of the PageViews tracked by this manager.
     *
     * @todo       Remove this function
     *
     * @deprecated Been replaced by getPageViewsFlattened()
     * @since      0.1.0
     *
     * @return PageView[][]
     */
    public function getAllPageViews()
    {
        return $this->trackedItemsFlattened;
    }

    /**
     * Get all of the PageViews in an associative array with PageView types as the keys.
     *
     * @since  0.1.1
     *
     * @return PageView[][]
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
     * @return PageView[]
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
     * @return PageView[]
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
     *
     * @since 0.1.0
     */
    public function parsePageViews($pageViewFolders)
    {
        if (empty($pageViewFolders))
        {
            return;
        }

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
                'fileExplorer' => FileExplorer::INCLUDE_ONLY_FILES,
            ), array('/.html$/', '/.twig$/'));
        }
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
        $this->trackedItems[PageView::DYNAMIC_TYPE][$collection]->addRepeatableItem($contentItem);
    }

    /**
     * {@inheritdoc}
     */
    protected function &handleTrackableItem($filePath, array $options = array())
    {
        $pageView = PageView::create($filePath);
        $namespace = $pageView->getType();

        switch ($namespace)
        {
            case PageView::STATIC_TYPE:
                $this->handleTrackableStaticPageView($pageView);
                break;

            case PageView::DYNAMIC_TYPE:
                $this->handleTrackableDynamicPageView($pageView);
                break;

            default:
                break;
        }

        $this->addObjectToTracker($pageView, $namespace);

        return $pageView;
    }

    /**
     * Handle special behavior and treatment for static PageViews while we're iterating through them.
     *
     * @param PageView $pageView
     *
     * @since 0.1.0
     */
    private function handleTrackableStaticPageView(&$pageView)
    {
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
     */
    private function handleTrackableDynamicPageView(&$pageView)
    {
        $frontMatter = $pageView->getFrontMatter(false);
        $namespace = (isset($frontMatter['collection'])) ? 'collection' : 'dataset';

        $collection = $frontMatter[$namespace];
        $array = $namespace . 's';

        if (!isset($this->{$array}[$collection]))
        {
            throw new CollectionNotFoundException("The '$collection' $namespace is not defined");
        }

        foreach ($this->{$array}[$collection] as &$item)
        {
            $item->evaluateFrontMatter($frontMatter);
            $item->setParentPageView($pageView);
            $item->buildPermalink(true);
            $pageView->addRepeatableItem($item);
        }
    }
}
