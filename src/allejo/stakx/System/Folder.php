<?php

/**
 * @copyright 2017 Vladimir Jimenez
 * @license   https://github.com/allejo/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\System;

use Symfony\Component\Filesystem\Exception\FileNotFoundException;
use Symfony\Component\Finder\SplFileInfo;

/**
 * All folder paths stored inside of this class will **not** have the ending DIRECTORY_SEPARATOR.
 *
 * @internal
 */
class Folder
{
    protected $fs;
    protected $absolutePath;
    protected $targetDirectories;

    public function __construct($folderPath)
    {
        $this->fs = new Filesystem();
        $this->targetDirectories = array();

        // Setup the absolute path to the directory
        if (substr($folderPath, 0, 1) === DIRECTORY_SEPARATOR)
        {
            $this->absolutePath = $folderPath;
        }
        else
        {
            $this->absolutePath = $this->fs->absolutePath($folderPath);
        }

        // Ensure our directory paths will don't end with a '/'
        $this->absolutePath = rtrim($this->absolutePath, DIRECTORY_SEPARATOR);

        if (!$this->fs->isDir($this->absolutePath))
        {
            throw new \InvalidArgumentException();
        }

        if (!$this->fs->exists($this->absolutePath))
        {
            throw new FileNotFoundException();
        }
    }

    public function __toString()
    {
        return rtrim($this->absolutePath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
    }

    /**
     * Set a base folder that will be prefixed before all file writes and copies.
     *
     * @param string $folderName
     *
     * @since 0.1.0
     */
    public function setTargetDirectory($folderName)
    {
        if (is_null($folderName) || empty($folderName))
        {
            $this->targetDirectories = array();
        }
        else
        {
            $this->targetDirectories[] = trim($folderName, DIRECTORY_SEPARATOR);
        }
    }

    /**
     * Copy a file from an absolute file to a path relative to the Folder's location.
     *
     * @param string $absolutePath The absolute path of the file
     * @param string $targetPath   The relative file path to the Folder's location
     *
     * @since 0.1.0
     */
    public function copyFile($absolutePath, $targetPath)
    {
        $targetPath = ltrim($targetPath, DIRECTORY_SEPARATOR);

        $this->fs->copy(
            $absolutePath,
            $this->buildPath($this->getCwd(), $targetPath),
            true
        );
    }

    /**
     * Write a file with the specified content.
     *
     * @param string $relativePath The file path relative to this Folder's location
     * @param string $content      The content that will be written to the file
     *
     * @since 0.1.0
     *
     * @return SplFileInfo
     */
    public function writeFile($relativePath, $content)
    {
        $outputFolder = $this->fs->getFolderPath($relativePath);
        $targetFileName = $this->fs->getFileName($outputFolder);

        $absoluteFolderPath = $this->buildPath($this->getCwd(), $outputFolder);

        if (!file_exists($absoluteFolderPath))
        {
            mkdir($absoluteFolderPath, 0755, true);
        }

        file_put_contents(
            $this->buildPath($this->getCwd(), $relativePath),
            $content
        );

        return (new SplFileInfo(
            $targetFileName,
            $absoluteFolderPath,
            $this->buildPath($absoluteFolderPath, $targetFileName)
        ));
    }

    /**
     * @param string $pathFragments
     *
     * @return string
     */
    private function buildPath($pathFragments)
    {
        $paths = func_get_args();

        return implode(DIRECTORY_SEPARATOR, $paths);
    }

    /**
     * Returns the absolute path of where files will be placed.
     *
     * @return string
     */
    private function getCwd()
    {
        $location = array_merge(array($this->absolutePath), $this->targetDirectories);

        return implode(DIRECTORY_SEPARATOR, $location);
    }
}
