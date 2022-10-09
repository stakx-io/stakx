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
 * This event is fired before a Repeater PageView is rendered and allows you inject custom variables into the templates.
 *
 * @since 0.2.0
 */
class CompilerPreRenderRepeaterPageView extends Event
{
    use CompilerPreRenderTrait;

    final public const NAME = 'compiler.prerender.repeater_pageview';

    public function __construct(private readonly RepeaterPageView $pageView, private readonly ExpandedValue $expandedValue)
    {
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
