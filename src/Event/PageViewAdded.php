<?php

/**
 * @copyright 2018 Vladimir Jimenez
 * @license   https://github.com/stakx-io/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Event;

use allejo\stakx\Document\BasePageView;
use allejo\stakx\Document\DynamicPageView;
use allejo\stakx\Document\RepeaterPageView;
use allejo\stakx\Document\StaticPageView;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * This event is fired whenever a PageView is registered. This event grants access to the actual PageView object to
 * allow for any modifications necessary.
 *
 * @since 0.2.0
 */
class PageViewAdded extends Event
{
    public const NAME = 'pageview.item.added';

    private DynamicPageView|RepeaterPageView|StaticPageView $pageView;

    public function __construct(DynamicPageView|RepeaterPageView|StaticPageView $pageView)
    {
        $this->pageView = &$pageView;
    }

    public function getPageView(): DynamicPageView|RepeaterPageView|StaticPageView
    {
        return $this->pageView;
    }
}
