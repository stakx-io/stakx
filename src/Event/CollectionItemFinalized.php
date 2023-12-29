<?php

/**
 * @copyright 2018 Vladimir Jimenez
 * @license   https://github.com/stakx-io/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Event;

use allejo\stakx\Document\ContentItem;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * This event is fired after a ContentItem has been finalized and tied to its respective parent PageViews. This event
 * grants readonly access to the object.
 *
 * @since 0.2.1
 */
class CollectionItemFinalized extends Event
{
    public const NAME = 'collection.item.finalized';

    private ContentItem $contentItem;

    public function __construct(ContentItem $contentItem)
    {
        $this->contentItem = $contentItem;
    }

    /**
     * A clone of the finalized ContentItem; it can no longer be modified at
     * this point.
     */
    public function getContentItem(): ContentItem
    {
        return clone $this->contentItem;
    }
}
