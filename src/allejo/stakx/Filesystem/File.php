<?php

/**
 * @copyright 2017 Vladimir Jimenez
 * @license   https://github.com/allejo/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Filesystem;

use allejo\stakx\Service;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;

/**
 * A representation of a file on a given filesystem, virtual or physical.
 *
 * This class extends \SplFileInfo and adds new methods along with overriding some methods solely because I feel that
 * some of the naming can be misleading.
 *
 * @since 0.2.0
 */
final class File extends \SplFileInfo
{
    /** @var string The path relative to the site's working directory. */
    private $relativePath;

    /** @var string The original raw path given to the constructor. */
    private $rawPath;

    /**
     * File Constructor.
     *
     * @param string $filePath An absolute file path or a path relative to the current working directory.
     *
     * @since 0.2.0
     */
    public function __construct($filePath)
    {
        $this->rawPath = $filePath;

        parent::__construct(self::realpath($filePath));

        $this->relativePath = str_replace(Service::getWorkingDirectory() . DIRECTORY_SEPARATOR, '', $this->getAbsolutePath());
    }

    /**
     * Get a new File object for another file relative to this file.
     *
     * @param string $path
     *
     * @return File
     */
    public function createFileForRelativePath($path)
    {
        return new File(Service::getWorkingDirectory() . DIRECTORY_SEPARATOR . $path);
    }

    /**
     * Whether or not this file exists on the filesystem.
     *
     * @return bool
     */
    public function exists()
    {
        return file_exists($this->getAbsolutePath());
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
        return $this->relativePath;
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
        $this->isSafeToRead();

        $content = file_get_contents($this->getAbsolutePath());

        if ($content === false)
        {
            $error = error_get_last();
            throw new \RuntimeException($error['message']);
        }

        return $content;
    }

    /**
     * Check if a file is safe to read.
     */
    private function isSafeToRead()
    {
        if (self::isVFS($this->getAbsolutePath()))
        {
            return;
        }

        if (strpos($this->getAbsolutePath(), Service::getWorkingDirectory()) !== 0 || !$this->exists())
        {
            throw new FileNotFoundException(
                sprintf('The given path "%s" does not exist or is outside the website working directory', $this->rawPath),
                0,
                null,
                $this->rawPath
            );
        }
    }

    /**
     * A vfsStream friendly way of getting the realpath() of something.
     *
     * @param string $path
     *
     * @return string
     */
    public static function realpath($path)
    {
        return self::isVFS($path) ? $path : realpath($path);
    }

    /**
     * Check whether a given path is on the virtual filesystem.
     *
     * @param string $path
     *
     * @return bool
     */
    private static function isVFS($path)
    {
        return substr($path, 0, 6) == 'vfs://';
    }
}
