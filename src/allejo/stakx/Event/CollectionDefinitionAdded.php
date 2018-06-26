<?php

/**
 * @copyright 2018 Vladimir Jimenez
 * @license   https://github.com/stakx-io/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Event;

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
    private $folderName;

    public function __construct($collectionName, $folderName)
    {
        $this->collectionName = $collectionName;
        $this->folderName = $folderName;
    }

    /**
     * @return string
     */
    public function getCollectionName()
    {
        return $this->collectionName;
    }

    /**
     * @return string
     */
    public function getFolderName()
    {
        return $this->folderName;
    }
}
