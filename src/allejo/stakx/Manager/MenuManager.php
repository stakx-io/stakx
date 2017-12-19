<?php

/**
 * @copyright 2017 Vladimir Jimenez
 * @license   https://github.com/allejo/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Manager;

use allejo\stakx\Document\JailedDocument;
use allejo\stakx\Document\StaticPageView;

class MenuManager extends BaseManager
{
    /** @var StaticPageView */
    private $siteMenu;

    public function __construct()
    {
        parent::__construct();

        $this->siteMenu = array();
    }

    /**
     * An array representing the website's menu structure with children and grandchildren made from static PageViews.
     *
     * @return JailedDocument[]
     */
    public function getSiteMenu()
    {
        $jailedMenu = array();

        foreach ($this->siteMenu as $key => $value)
        {
            // If it's an array, it means the parent is hidden from the site menu therefore its children should be too
            if (is_array($this->siteMenu[$key]))
            {
                continue;
            }

            $jailedMenu[$key] = $value->createJail();
        }

        return $jailedMenu;
    }

    /**
     * @param StaticPageView[] $pageViews
     */
    public function buildFromPageViews($pageViews)
    {
        foreach ($pageViews as $pageView)
        {
            $this->addToSiteMenu($pageView);
        }
    }

    /**
     * @param StaticPageView $pageView
     */
    public function addToSiteMenu($pageView)
    {
        $frontMatter = $pageView->getFrontMatter();

        if (isset($frontMatter['menu']) && !$frontMatter['menu'])
        {
            return;
        }

        $url = trim($pageView->getPermalink(), '/');

        // @TODO in the next breaking release, remove this check and allow the homepage to be indexed as '.'
        if (empty($url))
        {
            return;
        }

        $root = &$this->siteMenu;
        $dirs = explode('/', $url);

        while (count($dirs) > 0)
        {
            $name = array_shift($dirs);
            $name = (!empty($name)) ? $name : '.';

            if (!is_null($name) && count($dirs) == 0)
            {
                if (isset($root[$name]) && is_array($root[$name]))
                {
                    $children = &$pageView->getChildren();
                    $children = $root[$name]['children'];
                }

                $root[$name] = &$pageView;
            }
            else
            {
                if (!isset($root[$name]))
                {
                    $root[$name]['children'] = array();
                    $root = &$root[$name]['children'];
                }
                elseif (isset($root[$name]) && is_array($root[$name]))
                {
                    $root = &$root[$name]['children'];
                }
                else
                {
                    $root = &$root[$name]->getChildren();
                }
            }
        }
    }
}
