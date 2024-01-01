<?php

/**
 * @copyright 2018 Vladimir Jimenez
 * @license   https://github.com/stakx-io/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Filesystem;

use allejo\stakx\Service;

/**
 * A representation of a file on a given filesystem, virtual or physical.
 *
 * This class extends \SplFileInfo and adds new methods along with overriding some methods solely because I feel that
 * some of the naming can be misleading.
 *
 * @since 0.2.0
 */
final class File extends BaseFilesystemItem
{
    /**
     * Get a new File object for another file relative to this file.
     */
    public function createFileForRelativePath(string $path): File
    {
        return new File(Service::getWorkingDirectory() . DIRECTORY_SEPARATOR . $path);
    }

    /**
     * Get the name of the file without an extension.
     *
     * @param null $suffix this value will be discarded and is only needed to be able to override the \SplFileInfo
     *                     definition
     *
     * @since 0.2.0
     */
    public function getBasename($suffix = null): string
    {
        return parent::getBasename('.' . $this->getExtension());
    }

    /**
     * Get the name of the with the extension.
     *
     * @since 0.2.0
     */
    public function getFilename(): string
    {
        return $this->getFullName();
    }

    /**
     * Get the contents of this file.
     *
     * @since 0.2.0
     *
     * @throws \RuntimeException when the file could not be read
     */
    public function getContents(): string
    {
        if (!$this->exists())
        {
            throw $this->buildNotFoundException();
        }

        $content = file_get_contents($this->getAbsolutePath());

        if ($content === false)
        {
            $error = error_get_last();

            throw new \RuntimeException($error['message']);
        }

        return $content;
    }
}
