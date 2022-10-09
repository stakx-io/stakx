<?php

/**
 * @copyright 2018 Vladimir Jimenez
 * @license   https://github.com/stakx-io/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Event;

use allejo\stakx\Document\RepeaterPageView;
use allejo\stakx\FrontMatter\ExpandedValue;
use Symfony\Component\EventDispatcher\Event;

/**
 * This event is fired before a Repeater PageView is rendered and allows you inject custom variables into the templates.
 *
 * @since 0.2.0
 */
class CompilerPreRenderRepeaterPageView extends Event
{
    use CompilerPreRenderTrait;

    const NAME = 'compiler.prerender.repeater_pageview';

    private $pageView;
    private $expandedValue;

    public function __construct(RepeaterPageView $pageView, ExpandedValue $expandedValue)
    {
        $this->pageView = $pageView;
        $this->expandedValue = $expandedValue;
    }

    public function getPageView()
    {
        return clone $this->pageView;
    }

    public function getExpandedValue()
    {
        return clone $this->expandedValue;
    }
}
