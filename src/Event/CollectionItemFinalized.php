<?php declare(strict_types=1);

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
    final public const NAME = 'collection.item.finalized';

    private $contentItem;

    public function __construct(&$contentItem)
    {
        $this->contentItem = &$contentItem;
    }

    public function getContentItem(): ContentItem
    {
        return clone $this->contentItem;
    }
}
