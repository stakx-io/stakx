<?php

/**
 * @copyright 2017 Vladimir Jimenez
 * @license   https://github.com/allejo/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Filesystem;

use Symfony\Component\Filesystem\Exception\FileNotFoundException;

/**
 * A representation of a file on a given filesystem.
 *
 * This class extends \SplFileInfo and adds new methods along with overriding some methods solely because I feel that
 * some of the naming can be misleading.
 *
 * @since 0.2.0
 */
final class File extends \SplFileInfo
{
    private $relativePath;

    /**
     * File Constructor.
     *
     * @param string $absoluteFilePath  The absolute file path
     * @param string|null $relativePath The relative path to its parent folder with respect to the CWD
     *
     * @since 0.2.0
     */
    public function __construct($absoluteFilePath, $relativePath = null)
    {
        $this->relativePath = ($relativePath === null) ? getcwd() : $relativePath;

        parent::__construct($absoluteFilePath);
    }

    /**
     * Get the name of the file without an extension.
     *
     * @param  null $suffix This value will be discarded and is only needed to be able to override the \SplFileInfo
     *                      definition.
     *
     * @since 0.2.0
     *
     * @return string
     */
    public function getBasename($suffix = null)
    {
        return parent::getBasename('.' . $this->getExtension());
    }

    /**
     * Get the name of the with the extension.
     *
     * @since 0.2.0
     *
     * @return string
     */
    public function getFilename()
    {
        return parent::getBasename();
    }

    /**
     * Get the absolute path to this file.
     *
     * @since 0.2.0
     *
     * @return string
     */
    public function getAbsolutePath()
    {
        return $this->getPathname();
    }

    /**
     * Get the path to the parent folder of this file.
     *
     * @since 0.2.0
     *
     * @return string
     */
    public function getParentFolder()
    {
        return $this->getPath();
    }

    /**
     * Get the file path to this file, relative to where it was created; likely the current working directory.
     *
     * @since 0.2.0
     *
     * @return string
     */
    public function getRelativeFilePath()
    {
        $path = str_replace($this->relativePath, '', $this->getAbsolutePath());

        return ltrim($path, "/\\");
    }

    /**
     * Get the path to the parent folder this file, relative to where it was created; likely the current working directory.
     *
     * @since 0.2.0
     *
     * @return string
     */
    public function getRelativeParentFolder()
    {
        return dirname($this->getRelativeFilePath());
    }

    /**
     * Get the contents of this file.
     *
     * @since 0.2.0
     *
     * @throws \RuntimeException When the file could not be read.
     *
     * @return string
     */
    public function getContents()
    {
        if (!file_exists($this->getAbsolutePath()))
        {
            throw new FileNotFoundException(null, 0, null, $this->getAbsolutePath());
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
