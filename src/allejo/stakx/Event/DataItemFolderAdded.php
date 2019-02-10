<?php

/**
 * @copyright 2018 Vladimir Jimenez
 * @license   https://github.com/stakx-io/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Event;

use allejo\stakx\Filesystem\Folder;
use Symfony\Component\EventDispatcher\Event;

/**
 * A notification-only event fired whenever a new DataItem folder is scanned.
 *
 * @since 0.2.0
 */
class DataItemFolderAdded extends Event
{
    const NAME = 'dataitem.folder.added';

    private $folder;

    public function __construct(Folder $folderName)
    {
        $this->folder = $folderName;
    }

    /**
     * @return Folder
     */
    public function getFolder()
    {
        return $this->folder;
    }
}
