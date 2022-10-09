<?php declare(strict_types=1);

/**
 * @copyright 2018 Vladimir Jimenez
 * @license   https://github.com/stakx-io/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Filesystem;

use allejo\stakx\Filesystem\FilesystemLoader as fs;
use Exception;
use Stringable;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;

/**
 * A representation of a folder on a given filesystem.
 */
final class WritableFolder implements Stringable
{
    private bool $frozen;

    private readonly FilesystemPath $folder;

    public function __construct(string|FilesystemPath $folderPath)
    {
        $this->frozen = false;
        $this->folder = new FilesystemPath($folderPath);

        if (!$this->folder->isDir()) {
            throw new FileNotFoundException(sprintf('The folder could not be found: %s', $folderPath));
        }
    }

    public function __toString()
    {
        return $this->folder->getAbsolutePath();
    }

    /**
     * Get the file path to this Folder in an OOP friendly way.
     */
    public function getFilesystemPath(): FilesystemPath
    {
        return new FilesystemPath($this->__toString());
    }

    /**
     * Set this Folder to a "frozen" state meaning its path can no longer be modified.
     */
    public function freeze(): void
    {
        $this->frozen = true;
    }

    /**
     * Check whether or not this Folder's path has been frozen.
     */
    public function isFrozen(): bool
    {
        return $this->frozen;
    }

    /**
     * Set a base folder that will be prefixed before all file writes and copies.
     *
     * @since 0.2.0 An \Exception is thrown when a frozen Folder is attempted to
     *              be modified
     * @since 0.1.0
     *
     * @throws Exception
     */
    public function setTargetDirectory(string $folderName): void
    {
        if ($this->isFrozen()) {
            throw new Exception('A frozen folder object cannot be modified.');
        }

        if ($folderName === null || empty($folderName)) {
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
    public function copyFile($absolutePath, $targetPath): void
    {
        $targetPath = $this->folder->generatePath($targetPath);

        fs::copy($absolutePath, (string)$targetPath, true);
    }

    /**
     * Write a file with the specified content.
     *
     * @param string $relativePath The file path relative to this Folder's location
     * @param string $content      The content that will be written to the file
     *
     * @since 0.1.0
     */
    public function writeFile(string $relativePath, string $content): File
    {
        $targetFile = $this->folder->generatePath($relativePath);
        $targetFolderPath = (string)$targetFile->getParentDirectory();

        if (!file_exists($targetFolderPath)) {
            mkdir($targetFolderPath, 0755, true);
        }

        file_put_contents((string)$targetFile, $content);

        return new File($targetFile);
    }
}
