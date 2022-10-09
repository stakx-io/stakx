<?php declare(strict_types=1);

/**
 * @copyright 2018 Vladimir Jimenez
 * @license   https://github.com/stakx-io/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Event;

use allejo\stakx\Document\CollectableItem;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * This event is fired before a Dynamic PageView is rendered and allows you inject custom variables into the templates.
 *
 * @since 0.2.0
 */
class CompilerPreRenderDynamicPageView extends Event
{
    use CompilerPreRenderTrait;

    final public const NAME = 'compiler.prerender.dynamic_pageview';

    public function __construct(private readonly CollectableItem $collectableItem)
    {
    }

    public function getCollectableItem()
    {
        return clone $this->collectableItem;
    }
}
