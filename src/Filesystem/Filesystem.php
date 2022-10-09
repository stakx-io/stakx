<?php declare(strict_types=1);

/**
 * @copyright 2018 Vladimir Jimenez
 * @license   https://github.com/stakx-io/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Filesystem;

use allejo\stakx\Service;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;
use Symfony\Component\Filesystem\Exception\IOException;

/**
 * Class Filesystem.
 *
 * This class extends Symfony's Filesystem to provide convenience functions
 */
class Filesystem extends \Symfony\Component\Filesystem\Filesystem
{
    /**
     * Build an absolute file or directory path separated by the OS specific directory separator.
     *
     * @param string ...$pathFragments
     */
    public function absolutePath($pathFragments): string
    {
        if ($pathFragments instanceof FilesystemPath) {
            $pathFragments = (string)$pathFragments;
        }

        if ($this->isAbsolutePath($pathFragments)) {
            return $pathFragments;
        }

        $args = func_get_args();
        array_unshift($args, Service::getWorkingDirectory());

        return implode(DIRECTORY_SEPARATOR, $args);
    }

    /**
     * Build a file or directory path separated by the OS specific directory separator.
     *
     * @param string ...$pathFragments
     */
    public function appendPath($pathFragments): string
    {
        return implode(DIRECTORY_SEPARATOR, func_get_args());
    }

    /**
     * Copy a file or folder recursively.
     *
     * @param string $originFile          The original filename
     * @param string $targetFile          The target filename
     * @param bool   $overwriteNewerFiles If true, target files newer than origin files are overwritten
     *
     * @throws FileNotFoundException When originFile doesn't exist
     * @throws IOException           When copy fails
     */
    public function copy($originFile, $targetFile, $overwriteNewerFiles = false): void
    {
        if ($this->isDir($originFile)) {
            if (!$this->isDir($targetFile)) {
                mkdir($targetFile, 0755, true);
            }

            $dir = dir($originFile);

            while (false !== $entry = $dir->read()) {
                // Skip pointers
                if ($entry == '.' || $entry == '..') {
                    continue;
                }

                $this->copy("{$originFile}/{$entry}", "{$targetFile}/{$entry}", true);
            }

            $dir->close();
        } else {
            parent::copy($originFile, $targetFile, $overwriteNewerFiles);
        }
    }

    /**
     * Create an instance of stakx's File object with relative path information.
     *
     * @param string $filePath
     */
    public function createFileObject($filePath): File
    {
        return new File($this->absolutePath($filePath));
    }

    /**
     * Strip the current working directory from an absolute path.
     *
     * @param string $path An absolute path
     */
    public function getRelativePath($path): string
    {
        return str_replace(Service::getWorkingDirectory() . DIRECTORY_SEPARATOR, '', $path);
    }

    /**
     * Get the name of a given file without the extension.
     *
     * @param string $filePath A file path
     */
    public function getBaseName($filePath): string
    {
        return pathinfo($filePath, PATHINFO_FILENAME);
    }

    /**
     * Get the name of a given file.
     *
     * @param string $filePath A file path
     */
    public function getFileName($filePath): string
    {
        return pathinfo($filePath, PATHINFO_BASENAME);
    }

    /**
     * Get the parent directory of a given file.
     *
     * @param string $filePath A file path
     */
    public function getFolderPath($filePath): string
    {
        return pathinfo($filePath, PATHINFO_DIRNAME);
    }

    /**
     * Get the extension of a given file.
     *
     * @param string $filename A file path
     *
     * @return string The extension of the file
     */
    public function getExtension($filename): string
    {
        return pathinfo($filename, PATHINFO_EXTENSION);
    }

    /**
     * Get the MIME type of a file.
     *
     * @param string $filePath
     */
    public function getMimeType($filePath): string
    {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $filePath);
        finfo_close($finfo);

        return $mimeType;
    }

    /**
     * Check whether or not if a given path is a directory.
     *
     * @param string $folderPath
     */
    public function isDir($folderPath): bool
    {
        return is_dir($folderPath);
    }

    /**
     * Check whether or not a given path is a file.
     *
     * @param string $filePath
     */
    public function isFile($filePath): bool
    {
        return is_file($filePath);
    }

    /**
     * Check whether a given file path is a symlink.
     *
     * @param string $filePath
     */
    public function isSymlink($filePath): bool
    {
        return is_link($filePath);
    }

    /**
     * Get the full path to the file without the extension.
     *
     * @param string $filename A file path
     */
    public function removeExtension($filename): string
    {
        return $this->appendPath(
            $this->getFolderPath($filename),
            $this->getBaseName($filename)
        );
    }

    public function path($path)
    {
        return new FilesystemPath($path);
    }

    public function getInternalResource($name)
    {
        $path = new FilesystemPath(__DIR__ . '/../Resources/' . $name);

        return file_get_contents((string)$path);
    }

    /**
     * A vfsStream friendly way of getting the realpath() of something.
     *
     * @template T of string|BaseFilesystemItem
     *
     * @param T $path
     *
     * @return false|T
     */
    public function realpath(string|BaseFilesystemItem $path): false|string|BaseFilesystemItem
    {
        return $this->isVFS($path) ? $path : realpath((string)$path);
    }

    /**
     * Check whether a given path is on the virtual filesystem.
     */
    public function isVFS(string|BaseFilesystemItem $path): bool
    {
        return str_starts_with((string)$path, 'vfs://');
    }
}
