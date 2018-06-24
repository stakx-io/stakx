<?php

/**
 * @copyright 2018 Vladimir Jimenez
 * @license   https://github.com/stakx-io/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Event;

use Symfony\Component\EventDispatcher\Event;

/**
 * A notification-only event fired whenever a new DataItem folder is scanned.
 *
 * @since 0.2.0
 */
class DataItemFolderAdded extends Event
{
    const NAME = 'dataitem.folder.added';

    private $folderName;

    public function __construct($folderName)
    {
        $this->folderName = $folderName;
    }

    /**
     * @return string
     */
    public function getFolderName()
    {
        return $this->folderName;
    }
}
