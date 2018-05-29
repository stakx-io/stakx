<?php

/**
 * @copyright 2018 Vladimir Jimenez
 * @license   https://github.com/allejo/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Event;

use allejo\stakx\Document\BasePageView;
use allejo\stakx\Document\DynamicPageView;
use allejo\stakx\Document\RepeaterPageView;
use allejo\stakx\Document\StaticPageView;
use Symfony\Component\EventDispatcher\Event;

/**
 * This event is fired whenever a PageView is registered. This event grants access to the actual PageView object to
 * allow for any modifications necessary.
 *
 * @since 0.2.0
 */
class PageViewAdded extends Event
{
    const NAME = 'pageview.item.added';

    private $pageView;

    public function __construct(BasePageView &$pageView)
    {
        $this->pageView = &$pageView;
    }

    /**
     * @return BasePageView|DynamicPageView|RepeaterPageView|StaticPageView
     */
    public function &getPageView()
    {
        return $this->pageView;
    }
}
