<?php

namespace allejo\stakx;

use allejo\stakx\Document\BasePageView;
use allejo\stakx\FrontMatter\FrontMatterParser;

class PageViewRouter
{
    private $mapping;

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
                break;

            case BasePageView::DYNAMIC_TYPE:
            case BasePageView::REPEATER_TYPE:
                $rawFM = $pageView->getRawFrontMatter();
                $permalinkFM = \__::get($rawFM, 'permalink');

                $permalink = is_array($permalinkFM) ? $permalinkFM[0] : $permalinkFM;
                $permalink = preg_replace(FrontMatterParser::VARIABLE_DEF, '{$1}', $permalink);

                $this->mapping[$permalink] = $pageView;
                break;
        }
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
