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
 * This event is triggered after a Repeater PageView is compiled and before it is written to a file. The compiled output
 * is accessible and can be modified before it is written out to a file.
 *
 * @since 0.2.0
 */
class CompilerPostRenderRepeaterPageView extends Event
{
    use CompilerPostRenderTrait;

    const NAME = 'compiler.postrender.repeater_pageview';

    private $pageView;
    private $expandedValue;

    /**
     * @param string $compiledOutput
     */
    public function __construct(RepeaterPageView $pageView, ExpandedValue $expandedValue, $compiledOutput)
    {
        $this->pageView = $pageView;
        $this->expandedValue = $expandedValue;
        $this->compiledOutput = $compiledOutput;
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
