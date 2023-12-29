<?php

/**
 * @copyright 2018 Vladimir Jimenez
 * @license   https://github.com/stakx-io/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Event;

use allejo\stakx\Document\CollectableItem;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * This event is triggered after a Dynamic PageView is compiled and before it is written to a file. The compiled output
 * is accessible and can be modified before it is written out to a file.
 *
 * @since 0.2.0
 */
class CompilerPostRenderDynamicPageView extends Event
{
    use CompilerPostRenderTrait;

    public const NAME = 'compiler.postrender.dynamic_pageview';

    private CollectableItem $collectableItem;

    public function __construct(CollectableItem $collectableItem, string|\Stringable $compiledOutput)
    {
        $this->collectableItem = $collectableItem;
        $this->compiledOutput = $compiledOutput;
    }

    /**
     * A clone of the CollectableItem;
     */
    public function getCollectableItem(): CollectableItem
    {
        return clone $this->collectableItem;
    }
}
