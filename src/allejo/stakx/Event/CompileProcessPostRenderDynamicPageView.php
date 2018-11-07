<?php

/**
 * @copyright 2018 Vladimir Jimenez
 * @license   https://github.com/stakx-io/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Event;

use allejo\stakx\Document\BasePageView;
use allejo\stakx\Document\CollectableItem;
use allejo\stakx\Utilities\StrUtils;
use Symfony\Component\EventDispatcher\Event;

/**
 * This event is triggered after a Dynamic PageView is compiled and before it is written to a file. The compiled output
 * is accessible and can be modified before it is written out to a file.
 *
 * @since 0.2.0
 */
class CompileProcessPostRenderDynamicPageView extends Event
{
    use CompileProcessPostRenderTrait;

    const NAME = 'compile.process.postrender_dynamic_pageview';

    private $collectableItem;

    /**
     * @param string $compiledOutput
     */
    public function __construct(CollectableItem $collectableItem, $compiledOutput)
    {
        $this->collectableItem = $collectableItem;
        $this->compiledOutput = $compiledOutput;
    }

    public function getCollectableItem()
    {
        return clone $this->collectableItem;
    }
}
