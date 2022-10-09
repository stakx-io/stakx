<?php

/**
 * @copyright 2018 Vladimir Jimenez
 * @license   https://github.com/stakx-io/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Event;

use allejo\stakx\Filesystem\Folder;
use Symfony\Component\EventDispatcher\Event;

/**
 * A notification-only event fired whenever a new, valid Collection definition is registered.
 *
 * @since 0.2.0
 */
class CollectionDefinitionAdded extends Event
{
    const NAME = 'collection.definition.added';

    private $collectionName;
    private $folder;

    public function __construct($collectionName, Folder $folder)
    {
        $this->collectionName = $collectionName;
        $this->folder = $folder;
    }

    /**
     * @return string
     */
    public function getCollectionName()
    {
        return $this->collectionName;
    }

    /**
     * @return Folder
     */
    public function getFolder()
    {
        return $this->folder;
    }
}
