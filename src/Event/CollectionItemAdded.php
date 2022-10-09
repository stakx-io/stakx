<?php declare(strict_types=1);

/**
 * @copyright 2018 Vladimir Jimenez
 * @license   https://github.com/stakx-io/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Event;

use allejo\stakx\Document\ContentItem;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * This event is fired whenever a new ContentItem is created while processing a collection. This event grants access to
 * the ContentItem object allowing for any necessary modifications.
 *
 * @since 0.2.0
 */
class CollectionItemAdded extends Event
{
    final public const NAME = 'collection.item.added';

    private $contentItem;

    public function __construct(&$contentItem)
    {
        $this->contentItem = &$contentItem;
    }

    public function &getContentItem(): ContentItem
    {
        return $this->contentItem;
    }
}
