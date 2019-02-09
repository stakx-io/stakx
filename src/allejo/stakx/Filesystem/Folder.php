<?php

namespace allejo\stakx\Filesystem;

/**
 * A representation of a folder on a given filesystem, virtual or physical.
 *
 * This class extends \SplFileInfo and adds new methods along with overriding some methods solely because I feel that
 * some of the naming can be misleading.
 *
 * @since 0.2.0
 */
class Folder extends BaseFilesystemItem
{
    /**
     * Get the name of the with the extension.
     *
     * @since 0.2.0
     *
     * @return string
     */
    public function getFolderName()
    {
        return $this->getFullName();
    }
}
