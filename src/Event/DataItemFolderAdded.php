<?php

/**
 * @copyright 2018 Vladimir Jimenez
 * @license   https://github.com/stakx-io/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Event;

use allejo\stakx\Filesystem\Folder;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * A notification-only event fired whenever a new DataItem folder is scanned.
 *
 * @since 0.2.0
 */
class DataItemFolderAdded extends Event
{
    public const NAME = 'dataitem.folder.added';

    private Folder $folder;

    public function __construct(Folder $folderName)
    {
        $this->folder = $folderName;
    }

    public function getFolder(): Folder
    {
        return $this->folder;
    }
}
