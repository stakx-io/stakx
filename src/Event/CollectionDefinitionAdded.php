<?php

/**
 * @copyright 2018 Vladimir Jimenez
 * @license   https://github.com/stakx-io/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Event;

use allejo\stakx\Filesystem\Folder;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * A notification-only event fired whenever a new, valid Collection definition is registered.
 *
 * @since 0.2.0
 */
class CollectionDefinitionAdded extends Event
{
    public const NAME = 'collection.definition.added';

    private string $collectionName;
    private Folder $folder;

    public function __construct($collectionName, Folder $folder)
    {
        $this->collectionName = $collectionName;
        $this->folder = $folder;
    }

    public function getCollectionName(): string
    {
        return $this->collectionName;
    }

    public function getFolder(): Folder
    {
        return $this->folder;
    }
}
