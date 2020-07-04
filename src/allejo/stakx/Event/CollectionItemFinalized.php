<?php

/**
 * @copyright 2018 Vladimir Jimenez
 * @license   https://github.com/stakx-io/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Event;

use allejo\stakx\Document\ContentItem;
use Symfony\Component\EventDispatcher\Event;

/**
 * This event is fired after a ContentItem has been finalized and tied to its respective parent PageViews. This event
 * grants readonly access to the object.
 *
 * @since 0.2.1
 */
class CollectionItemFinalized extends Event
{
    const NAME = 'collection.item.finalized';

    private $contentItem;

    public function __construct(&$contentItem)
    {
        $this->contentItem = &$contentItem;
    }

    /**
     * @return ContentItem
     */
    public function getContentItem()
    {
        return clone $this->contentItem;
    }
}
