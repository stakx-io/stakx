<?php

/**
 * @copyright 2018 Vladimir Jimenez
 * @license   https://github.com/stakx-io/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Event;

use allejo\stakx\Document\StaticPageView;
use Symfony\Component\EventDispatcher\Event;

/**
 * This event is triggered after a Static PageView is compiled and before it is written to a file. The compiled output
 * is accessible and can be modified before it is written out to a file.
 *
 * @since 0.2.0
 */
class CompilerPostRenderStaticPageView extends Event
{
    use CompilerPostRenderTrait;

    const NAME = 'compiler.postrender.static_pageview';

    private $pageView;

    /**
     * @param string $compiledOutput
     */
    public function __construct(StaticPageView $pageView, $compiledOutput)
    {
        $this->pageView = $pageView;
        $this->compiledOutput = $compiledOutput;
    }

    public function getPageView()
    {
        return clone $this->pageView;
    }
}
