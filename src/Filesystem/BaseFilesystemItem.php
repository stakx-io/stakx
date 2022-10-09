<?php declare(strict_types=1);

/**
 * @copyright 2018 Vladimir Jimenez
 * @license   https://github.com/stakx-io/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Filesystem;

use allejo\stakx\Filesystem\FilesystemLoader as fs;
use allejo\stakx\Service;
use SplFileInfo;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;

class BaseFilesystemItem extends SplFileInfo
{
    /** @var string The path relative to the site's working directory. */
    protected string $relativePath;

    /**
     * @param string|FilesystemPath $rawPath an absolute file path or a path relative to the current working directory
     *
     * @since 0.2.0
     *
     * @throws FileNotFoundException
     */
    public function __construct(protected string|FilesystemPath $rawPath)
    {
        $realPath = fs::realpath($rawPath);

        if ($realPath === false) {
            throw $this->buildNotFoundException();
        }

        parent::__construct($realPath);

        $this->relativePath = str_replace(Service::getWorkingDirectory() . DIRECTORY_SEPARATOR, '', $this->getAbsolutePath());

        $this->isSafeToRead();
    }

    /**
     * Whether this file exists on the filesystem.
     */
    public function exists(): bool
    {
        return file_exists($this->getAbsolutePath());
    }

    /**
     * Get the absolute path to this file.
     *
     * @since 0.2.0
     */
    public function getAbsolutePath(): string
    {
        return $this->getPathname();
    }

    /**
     * Get the path to the parent folder of this file.
     *
     * @since 0.2.0
     */
    public function getParentFolder(): string
    {
        return $this->getPath();
    }

    /**
     * Get the file path to this file, relative to where it was created; likely the current working directory.
     *
     * @since 0.2.0
     */
    public function getRelativeFilePath(): string
    {
        return $this->relativePath;
    }

    /**
     * Get the path to the parent folder this file, relative to where it was created; likely the current working directory.
     *
     * @since 0.2.0
     */
    public function getRelativeParentFolder(): string
    {
        return dirname($this->getRelativeFilePath());
    }

    /**
     * Gets the last modified time.
     *
     * @return int The last modified time for the file, in a Unix timestamp
     */
    public function getLastModified(): int
    {
        return $this->getMTime();
    }

    /**
     * Get the full name of this file or folder.
     */
    protected function getFullName(): string
    {
        return parent::getBasename();
    }

    /**
     * Check if a file is safe to read.
     *
     * @throws FileNotFoundException
     */
    protected function isSafeToRead(): void
    {
        if (fs::isVFS($this->getAbsolutePath())) {
            return;
        }

        if (!str_starts_with($this->getAbsolutePath(), (string)Service::getWorkingDirectory())) {
            throw $this->buildNotFoundException();
        }
    }

    protected function buildNotFoundException(): FileNotFoundException
    {
        return new FileNotFoundException(
            sprintf('The given path "%s" does not exist or is outside the website working directory', $this->rawPath),
            0,
            null,
            $this->rawPath
        );
    }
}
