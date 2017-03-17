<?php

/**
 * @copyright 2017 Vladimir Jimenez
 * @license   https://github.com/allejo/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Manager;

use allejo\stakx\Exception\CollectionNotFoundException;
use allejo\stakx\Object\ContentItem;
use allejo\stakx\Object\DynamicPageView;
use allejo\stakx\Object\PageView;
use allejo\stakx\System\FileExplorer;

/**
 * This class is responsible for handling all of the PageViews within a website.
 *
 * PageManager will parse all available dynamic and static PageViews. After, dynamic PageViews will be prepared by
 * setting the appropriate values for each ContentItem such as permalinks. Lastly, this class will compile all of the
 * PageViews and write them to the target directory.
 *
 * @package allejo\stakx\Manager
 */
class PageManager extends TrackingManager
{
    /**
     * The relative (to the stakx project) file path to the redirect template
     *
     * @var string|bool
     */
    private $redirectTemplate;

    /**
     * @var PageView[]
     */
    private $twigExtendsDeps;

    /**
     * @var ContentItem[][]
     */
    private $collections;

    /**
     * @var PageView[]
     */
    private $flatPages;

    /**
     * PageManager constructor
     */
    public function __construct()
    {
        parent::__construct();

        $this->redirectTemplate = false;
        $this->twigExtendsDeps = array();
        $this->collections = array();
        $this->flatPages = array();
    }

    /**
     * Give this manager the collections we'll be using for dynamic PageViews
     *
     * @param ContentItem[][] $collections
     */
    public function setCollections (&$collections)
    {
        $this->collections = &$collections;
    }

    public function getStaticPages ()
    {
        return $this->flatPages;
    }

    public function getJailedStaticPages ()
    {
        $jailedObjects = array();

        foreach ($this->flatPages as $key => $value)
        {
            // If it's an array, it means the parent is hidden from the site menu therefore its children should be too
            if (is_array($value))
            {
                continue;
            }

            $jailedObjects[$key] = $value->createJail();
        }

        return $jailedObjects;
    }

    /**
     * Go through all of the PageView directories and create a respective PageView for each and classify them as a
     * dynamic or static PageView.
     *
     * @param $pageViewFolders
     */
    public function parsePageViews ($pageViewFolders)
    {
        if (empty($pageViewFolders)) { return; }

        /**
         * The name of the folder where PageViews are located
         *
         * @var $pageViewFolder string
         */
        foreach ($pageViewFolders as $pageViewFolderName)
        {
            $pageViewFolder = $this->fs->absolutePath($pageViewFolderName);

            if (!$this->fs->exists($pageViewFolder))
            {
                continue;
            }

            $this->scanTrackableItems($pageViewFolder, array(
                'fileExplorer' => FileExplorer::INCLUDE_ONLY_FILES
            ), array('/.html$/', '/.twig$/'));
            $this->saveFolderDefinition($pageViewFolderName);
        }
    }

    /**
     * Add a new ContentItem to the respective parent PageView of the ContentItem
     *
     * @param ContentItem $contentItem
     */
    public function updatePageView ($contentItem)
    {
        /** @var DynamicPageView $pageView */
        foreach ($this->trackedItems['dynamic'] as &$pageView)
        {
            $fm = $pageView->getFrontMatter(false);

            if ($fm['collection'] == $contentItem->getCollection())
            {
                $pageView->addContentItem($contentItem);
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function isTracked($filePath)
    {
        return (parent::isTracked($filePath) || isset($this->twigExtendsDeps[$filePath]));
    }

    /**
     * @return PageView[]
     */
    public function getPageViews ()
    {
        return $this->trackedItemsFlattened;
    }

    /**
     * {@inheritdoc}
     */
    protected function handleTrackableItem($filePath, $options = array())
    {
        $pageView  = PageView::create($filePath);
        $namespace = $pageView->getType();

        switch ($namespace)
        {
            case PageView::DYNAMIC_TYPE:
                $this->handleTrackableDynamicPageView($pageView);
                break;

            case PageView::STATIC_TYPE:
                $this->handleTrackableStaticPageView($pageView);
                break;

            default:
                break;
        }

        $this->addObjectToTracker($pageView, $pageView->getRelativeFilePath(), $namespace);
        $this->saveTrackerOptions($pageView->getRelativeFilePath(), array(
            'viewType' => $namespace
        ));
    }

    /**
     * @param DynamicPageView $pageView
     */
    private function handleTrackableDynamicPageView ($pageView)
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

    /**
     * @param PageView $pageView
     */
    private function handleTrackableStaticPageView ($pageView)
    {
        if (empty($pageView['title'])) { return; }

        $this->flatPages[$pageView['title']] = $pageView;
    }
}