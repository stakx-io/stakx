<?php

/**
 * @copyright 2018 Vladimir Jimenez
 * @license   https://github.com/allejo/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Event;

use allejo\stakx\Document\ContentItem;
use Symfony\Component\EventDispatcher\Event;

/**
 * This event is fired whenever a new ContentItem is created while processing a collection. This event grants access to
 * the ContentItem object allowing for any necessary modifications.
 *
 * @since 0.2.0
 */
class CollectionItemAdded extends Event
{
    const NAME = 'collection.item.added';

    private $contentItem;

    public function __construct(&$contentItem)
    {
        $this->contentItem = &$contentItem;
    }

    /**
     * @return ContentItem
     */
    public function &getContentItem()
    {
        return $this->contentItem;
    }
}
