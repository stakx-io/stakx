<?php

/**
 * @copyright 2018 Vladimir Jimenez
 * @license   https://github.com/stakx-io/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Event;

use allejo\stakx\Document\StaticPageView;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * This event is triggered after a Static PageView is compiled and before it is written to a file. The compiled output
 * is accessible and can be modified before it is written out to a file.
 *
 * @since 0.2.0
 */
class CompilerPostRenderStaticPageView extends Event
{
    use CompilerPostRenderTrait;

    public const NAME = 'compiler.postrender.static_pageview';

    private StaticPageView $pageView;

    public function __construct(StaticPageView $pageView, string|\Stringable $compiledOutput)
    {
        $this->pageView = $pageView;
        $this->compiledOutput = $compiledOutput;
    }

    /**
     * A copy of the Static PageView that triggered this event.
     */
    public function getPageView(): StaticPageView
    {
        return clone $this->pageView;
    }
}
