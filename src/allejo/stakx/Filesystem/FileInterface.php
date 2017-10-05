<?php

/**
 * @copyright 2017 Vladimir Jimenez
 * @license   https://github.com/allejo/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Filesystem;

interface FileInterface
{
    /**
     * Get a relative file relative to the root of the stakx website.
     *
     * @return string
     */
    public function getRelativeFilePath();

    /**
     * Get the extension of a file.
     *
     * @return string
     */
    public function getExtension();

    /**
     * Get the name of the file without *any* extensions.
     *
     * @return string
     */
    public function getBaseName();

    /**
     * Get the absolute file path.
     *
     * @return string
     */
    public function getAbsoluteFilePath();

    /**
     * Get the name of the file with the extension.
     *
     * @return string
     */
    public function getFileName();

    /**
     * Read the contents of a file.
     *
     * This function is responsible for reading the file and doing whatever is needed to parse & save the contents into
     * the given object.
     *
     * @return void
     */
    public function refreshFileContent();
}
