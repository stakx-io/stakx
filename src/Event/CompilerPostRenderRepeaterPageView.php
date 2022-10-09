<?php declare(strict_types=1);

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

    final public const NAME = 'compiler.postrender.repeater_pageview';

    /**
     * @param string $compiledOutput
     */
    public function __construct(private readonly RepeaterPageView $pageView, private readonly ExpandedValue $expandedValue, $compiledOutput)
    {
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
