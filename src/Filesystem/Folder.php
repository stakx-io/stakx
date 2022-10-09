<?php declare(strict_types=1);

/**
 * @copyright 2018 Vladimir Jimenez
 * @license   https://github.com/stakx-io/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Filesystem;

/**
 * A representation of a folder on a given filesystem, virtual or physical.
 *
 * This class extends \SplFileInfo and adds new methods along with overriding some methods solely because I feel that
 * some of the naming can be misleading.
 *
 * @since 0.2.0
 */
final class Folder extends BaseFilesystemItem
{
    /**
     * Get the name of the with the extension.
     *
     * @since 0.2.0
     */
    public function getFolderName(): string
    {
        return $this->getFullName();
    }
}
