<?php

/**
 * @copyright 2018 Vladimir Jimenez
 * @license   https://github.com/stakx-io/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Filesystem;

use allejo\stakx\Filesystem\FilesystemLoader as fs;

/**
 * A cross-platform filesystem path wrapper.
 *
 * This class is a wrapper for handling file paths in stakx in a cross-platform way. Give it a Windows path and append
 * a Unix style path and it'll just work.
 */
final class FilesystemPath
{
    /** @var string */
    private $absolutePath;
    /** @var string */
    private $originalPath;
    /** @var bool */
    private $isWindows;

    /**
     * @param string $filePath
     * @param string $dirSep
     */
    public function __construct($filePath, $dirSep = DIRECTORY_SEPARATOR)
    {
        $this->originalPath = $filePath;
        $this->isWindows = ($dirSep === '\\');

        if ($this->isWindows)
        {
            $filePath = $this->unixifyPath($filePath);
        }

        $this->absolutePath = (fs::isAbsolutePath($filePath)) ? $filePath : fs::absolutePath($filePath);
    }

    public function __toString()
    {
        return $this->getAbsolutePath();
    }

    /**
     * Append a path to a directory path.
     *
     * @param string $append The path to append
     */
    public function appendToPath($append)
    {
        if ($this->isFile(false))
        {
            throw new \InvalidArgumentException("Appending to a file's path is not possible");
        }

        $this->absolutePath = $this->buildPath($this->absolutePath, $this->unixifyPath($append));
    }

    /**
     * Generate a path based off this file path.
     *
     * This method will not modify the existing file path of this instance, use FilesystemPath::appendToPath() for that.
     *
     * @param string $append
     *
     * @return string
     */
    public function generatePath($append)
    {
        return $this->buildPath($this->absolutePath, $this->unixifyPath($append));
    }

    /**
     * Get the absolute path of the file path.
     *
     * @return string
     */
    public function getAbsolutePath()
    {
        if ($this->isWindows)
        {
            return str_replace('/', '\\', $this->absolutePath);
        }

        return $this->absolutePath;
    }

    /**
     * Check whether the given path is a directory.
     *
     * @param bool $checkExistence When set to true, it will check the filesystem for the existence of the directory.
     *                             When set to false, this function will guess based on the path ending in a directory
     *                             separator.
     *
     * @return bool
     */
    public function isDir($checkExistence = true)
    {
        $absPath = $this->absolutePath;

        if ($checkExistence)
        {
            return file_exists($absPath) && is_dir($absPath);
        }

        return substr($absPath, -1, 1) == '/';
    }

    /**
     * Check whether the given path is a file.
     *
     * @param bool $checkExistence When set to true, it will check the filesystem for the existence of the file. When
     *                             set to false, this function will guess based on the path ending in a directory
     *                             separator.
     *
     * @return bool
     */
    public function isFile($checkExistence = true)
    {
        $absPath = $this->absolutePath;

        if ($checkExistence)
        {
            return file_exists($absPath) && is_file($absPath);
        }

        return !$this->isDir($checkExistence);
    }

    /**
     * Build a path from multiple strings.
     *
     * This function will _always_ use the '/' as the directory separator, because internal that's all stakx will use.
     * The FilesystemPath::getAbsolutePath() function will worry about Windows paths when necessary.
     *
     * @return string
     */
    private function buildPath()
    {
        $paths = [];

        foreach (func_get_args() as $arg)
        {
            if ($arg !== '')
            {
                $paths[] = $arg;
            }
        }

        return preg_replace('#(?<!:)/+#', '/', join('/', $paths));
    }

    /**
     * Convert a Windows path into a blasphemous Unix path.
     *
     * @param string $filePath
     *
     * @return string
     */
    private function unixifyPath($filePath)
    {
        return str_replace('\\', '/', $filePath);
    }
}
