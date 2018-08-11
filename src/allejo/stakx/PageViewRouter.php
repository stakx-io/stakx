<?php

namespace allejo\stakx;

use __\__;
use allejo\stakx\Document\BasePageView;
use allejo\stakx\Document\DynamicPageView;
use allejo\stakx\Document\RepeaterPageView;
use allejo\stakx\Document\StaticPageView;
use allejo\stakx\FrontMatter\FrontMatterParser;

class PageViewRouter
{
    private $mapping;
    private $redirects;

    public function __construct()
    {
        $this->mapping = [];
    }

    /**
     * Register a PageView to this router.
     */
    public function registerPageView(BasePageView $pageView)
    {
        switch ($pageView->getType())
        {
            case BasePageView::STATIC_TYPE:
                $this->mapping[$pageView->getPermalink()] = $pageView;

                foreach ($pageView->getRedirects() as $redirect)
                {
                    $this->redirects[$redirect] = $pageView->getPermalink();
                }

                break;

            case BasePageView::DYNAMIC_TYPE:
            case BasePageView::REPEATER_TYPE:
                $rawFM = $pageView->getRawFrontMatter();
                $permalinkFM = \__::get($rawFM, 'permalink');

                $permalink = is_array($permalinkFM) ? $permalinkFM[0] : $permalinkFM;
                $permalink = preg_replace(FrontMatterParser::VARIABLE_DEF, '{$1}', $permalink);
                $permalink = preg_replace(FrontMatterParser::ARRAY_DEF, '{$1}', $permalink);

                $this->mapping[$permalink] = $pageView;
                break;
        }
    }

    /**
     * Get the PageView used for a specified route.
     *
     * @param string $route
     *
     * @return StaticPageView|DynamicPageView|RepeaterPageView|null
     */
    public function getRoute($route)
    {
        return __::get($this->mapping, $route);
    }

    /**
     * Retrieve all of the URL routes registered.
     *
     * @return string[]
     */
    public function getRoutes()
    {
        return array_keys($this->mapping);
    }
}
