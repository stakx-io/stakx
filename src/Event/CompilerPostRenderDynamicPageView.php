<?php declare(strict_types=1);

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

    final public const NAME = 'compiler.postrender.dynamic_pageview';

    /**
     * @param string $compiledOutput
     */
    public function __construct(private readonly CollectableItem $collectableItem, $compiledOutput)
    {
        $this->compiledOutput = $compiledOutput;
    }

    public function getCollectableItem()
    {
        return clone $this->collectableItem;
    }
}
