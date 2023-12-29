<?php

/**
 * @copyright 2018 Vladimir Jimenez
 * @license   https://github.com/stakx-io/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Event;

use allejo\stakx\Document\RepeaterPageView;
use allejo\stakx\FrontMatter\ExpandedValue;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * This event is triggered after a Repeater PageView is compiled and before it is written to a file. The compiled output
 * is accessible and can be modified before it is written out to a file.
 *
 * @since 0.2.0
 */
class CompilerPostRenderRepeaterPageView extends Event
{
    use CompilerPostRenderTrait;

    public const NAME = 'compiler.postrender.repeater_pageview';

    private RepeaterPageView $pageView;
    private ExpandedValue $expandedValue;

    public function __construct(RepeaterPageView $pageView, ExpandedValue $expandedValue, string|\Stringable $compiledOutput)
    {
        $this->pageView = $pageView;
        $this->expandedValue = $expandedValue;
        $this->compiledOutput = $compiledOutput;
    }

    /**
     * A copy of the Repeater PageView that triggered this event.
     */
    public function getPageView(): RepeaterPageView
    {
        return clone $this->pageView;
    }

    /**
     * A copy of the expanded value of this Repeater PageView that triggered this event.
     */
    public function getExpandedValue(): ExpandedValue
    {
        return clone $this->expandedValue;
    }
}
