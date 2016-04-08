<?php

namespace allejo\stakx\Manager;

use allejo\stakx\Object\ContentItem;
use allejo\stakx\Object\PageView;
use allejo\stakx\System\Filesystem;
use Symfony\Component\Finder\Finder;

class PageManager
{
    /**
     * @var PageView[]
     */
    private $dynamicPageViews;

    /**
     * @var PageView[]
     */
    private $staticPageViews;
    private $siteMenu;
    private $fs;

    public function __construct()
    {
        $this->dynamicPageViews = array();
        $this->staticPageViews  = array();
        $this->fs               = new Filesystem();
    }

    public function parsePageViews ($pageViewFolders)
    {
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

            $finder = new Finder();
            $finder->files()
                   ->ignoreDotFiles(true)
                   ->ignoreUnreadableDirs()
                   ->in($pageViewFolder);

            foreach ($finder as $viewFile)
            {
                $newPageView = new PageView($viewFile);

                if ($newPageView->isDynamicPage())
                {
                    $this->dynamicPageViews[] = $newPageView;
                }
                else
                {
                    $this->addToSiteMenu($newPageView->getFrontMatter());
                    $this->staticPageViews[] = $newPageView;
                }
            }
        }
    }

    /**
     * Go through all of the dynamic PageViews and prepare the necessary information for each one.
     *
     * For example, permalinks are dynamic generated based on FrontMatter so this function sets the permalink for each
     * ContentItem in a collection. This is called before dynamic PageViews are compiled in order to allow access to
     * this information to Twig by the time it is compiled.
     *
     * @param ContentItem[] $collections
     */
    public function prepareDynamicPageViews ($collections)
    {
        foreach ($this->dynamicPageViews as $pageView)
        {
            $frontMatter = $pageView->getFrontMatter(false);
            $collection = $frontMatter['collection'];

            if (empty($collections[$collection]))
            {
                continue;
            }

            /** @var $item ContentItem */
            foreach ($collections[$collection] as $item)
            {
                $itemFrontMatter = $item->getFrontMatter();
                $item->setPermalink($pageView->getPermalink(), $itemFrontMatter);
            }
        }
    }

    /**
     * Add a static PageView to the menu array. Dynamic PageViews are not added to the menu
     *
     * @param array $frontMatter
     */
    private function addToSiteMenu ($frontMatter)
    {
        if (!array_key_exists('permalink', $frontMatter) ||
            (array_key_exists('menu', $frontMatter) && !$frontMatter['menu']))
        {
            return;
        }

        $url = $frontMatter['permalink'];
        $root = &$this->siteMenu;
        $permalink = trim($url, DIRECTORY_SEPARATOR);
        $dirs = explode(DIRECTORY_SEPARATOR, $permalink);

        while (count($dirs) > 0)
        {
            $name = array_shift($dirs);
            $name = (!empty($name)) ? $name : '.';

            if (!isset($root[$name]) && !is_null($name) && count($dirs) == 0)
            {
                $link = (pathinfo($url, PATHINFO_EXTENSION) !== "") ? $url : $permalink . DIRECTORY_SEPARATOR;

                $root[$name] = array_merge($frontMatter, array(
                    "url"  => '/' . $link,
                    "children" => array()
                ));
            }

            $root = &$root[$name]['children'];
        }
    }

    /**
     * @return PageView[]
     */
    public function getDynamicPageViews()
    {
        return $this->dynamicPageViews;
    }

    /**
     * @return PageView[]
     */
    public function getStaticPageViews()
    {
        return $this->staticPageViews;
    }

    /**
     * @return array
     */
    public function getSiteMenu ()
    {
        return $this->siteMenu;
    }
}