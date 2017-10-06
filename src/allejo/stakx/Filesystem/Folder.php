<?php

/**
 * @copyright 2017 Vladimir Jimenez
 * @license   https://github.com/allejo/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Filesystem;

use allejo\stakx\System\Filesystem;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;

/**
 * A representation of a folder on a given filesystem.
 */
final class Folder
{
    /** @var Filesystem */
    private $fs;
    /** @var FilesystemPath */
    private $folder;

    /**
     * @param string $folderPath
     */
    public function __construct($folderPath)
    {
        $this->fs = new Filesystem();
        $this->folder = new FilesystemPath($folderPath);

        if (!$this->folder->isDir())
        {
            throw new FileNotFoundException(sprintf('The folder could not be found: %s', $folderPath));
        }
    }

    public function __toString()
    {
        return $this->folder->getAbsolutePath();
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
        if ($folderName === null || empty($folderName))
        {
            return;
        }

        $this->folder->appendToPath($folderName);
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
        $targetPath = $this->folder->generatePath($targetPath);

        $this->fs->copy($absolutePath, $targetPath, true);
    }

    /**
     * Write a file with the specified content.
     *
     * @param string $relativePath The file path relative to this Folder's location
     * @param string $content      The content that will be written to the file
     *
     * @since 0.1.0
     *
     * @return File
     */
    public function writeFile($relativePath, $content)
    {
        $targetFile = $this->folder->generatePath($relativePath);
        $targetFolderPath = $this->fs->getFolderPath($targetFile);

        if (!file_exists($targetFolderPath))
        {
            mkdir($targetFolderPath, 0755, true);
        }

        file_put_contents($targetFile, $content);

        return (new File(
            $this->fs->getFileName($targetFile),
            $this->fs->getFolderPath($relativePath),
            $relativePath
        ));
    }
}
