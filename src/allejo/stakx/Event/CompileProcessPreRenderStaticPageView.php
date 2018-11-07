<?php

/**
 * @copyright 2018 Vladimir Jimenez
 * @license   https://github.com/stakx-io/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Event;

use allejo\stakx\Document\StaticPageView;
use Symfony\Component\EventDispatcher\Event;

/**
 * This event is fired before a PageView is rendered and allows you inject custom variables into the templates.
 *
 * @since 0.2.0
 */
class CompileProcessPreRenderStaticPageView extends Event
{
    use CompileProcessPreRenderTrait;

    const NAME = 'compile.process.prerender_static_pageview';

    private $pageView;

    public function __construct(StaticPageView $pageView)
    {
        $this->pageView = $pageView;
    }

    public function getPageView()
    {
        return clone $this->pageView;
    }
}
