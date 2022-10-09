<?php

namespace allejo\stakx\Filesystem;

use allejo\stakx\Service;
use allejo\stakx\Filesystem\FilesystemLoader as fs;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;

class BaseFilesystemItem extends \SplFileInfo
{
    /** @var string The path relative to the site's working directory. */
    protected $relativePath;

    /** @var string The original raw path given to the constructor. */
    protected $rawPath;

    /**
     * @param string $filePath an absolute file path or a path relative to the current working directory
     *
     * @since 0.2.0
     *
     * @throws FileNotFoundException
     */
    public function __construct($filePath)
    {
        $this->rawPath = $filePath;
        $realPath = fs::realpath($filePath);

        if ($realPath === false)
        {
            throw $this->buildNotFoundException();
        }

        parent::__construct($realPath);

        $this->relativePath = str_replace(Service::getWorkingDirectory() . DIRECTORY_SEPARATOR, '', $this->getAbsolutePath());

        $this->isSafeToRead();
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
     * Gets the last modified time.
     *
     * @return int The last modified time for the file, in a Unix timestamp
     */
    public function getLastModified()
    {
        return $this->getMTime();
    }

    /**
     * Get the full name of this file or folder.
     *
     * @return string
     */
    protected function getFullName()
    {
        return parent::getBasename();
    }

    /**
     * Check if a file is safe to read.
     *
     * @throws FileNotFoundException
     */
    protected function isSafeToRead()
    {
        if (fs::isVFS($this->getAbsolutePath()))
        {
            return;
        }

        if (strpos($this->getAbsolutePath(), Service::getWorkingDirectory()) !== 0)
        {
            throw $this->buildNotFoundException();
        }
    }

    protected function buildNotFoundException()
    {
        return new FileNotFoundException(
            sprintf('The given path "%s" does not exist or is outside the website working directory', $this->rawPath),
            0,
            null,
            $this->rawPath
        );
    }
}
