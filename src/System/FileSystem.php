<?php

/**
 * This file contains an extended Filesystem class.
 *
 * This file is part of the Stakx project.
 *
 * @copyright 2016 Vladimir Jimenez
 * @license   https://github.com/allejo/stakx/blob/master/LICENSE.md
 */

namespace allejo\stakx\System;

use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;
use Symfony\Component\Finder\SplFileInfo;

/**
 * Class Filesystem
 *
 * This class extends Symfony's Filesystem to provide convenience functions
 *
 * @package allejo\stakx\Environment
 */
class Filesystem extends \Symfony\Component\Filesystem\Filesystem
{
    /**
     * Build an absolute file or directory path separated by the OS specific directory separator
     *
     * @param string ...$pathFragments
     *
     * @return string
     */
    public function absolutePath ($pathFragments)
    {
        if ($this->isAbsolutePath($pathFragments))
        {
            return $pathFragments;
        }

        $args = func_get_args();
        array_unshift($args, getcwd());

        return implode(DIRECTORY_SEPARATOR, $args);
    }

    /**
     * Build a file or directory path separated by the OS specific directory separator
     *
     * @param string ...$pathFragments
     *
     * @return string
     */
    public function appendPath ($pathFragments)
    {
        return implode(DIRECTORY_SEPARATOR, func_get_args());
    }

    /**
     * Copy a file or folder recursively
     *
     * @param string $originFile          The original filename
     * @param string $targetFile          The target filename
     * @param bool   $overwriteNewerFiles If true, target files newer than origin files are overwritten
     *
     * @throws FileNotFoundException When originFile doesn't exist
     * @throws IOException           When copy fails
     */
    public function copy($originFile, $targetFile, $overwriteNewerFiles = false)
    {
        if ($this->isDir($originFile))
        {
            if (!$this->isDir($targetFile))
            {
                mkdir($targetFile, 0755, true);
            }

            $dir = dir($originFile);

            while (false !== $entry = $dir->read())
            {
                // Skip pointers
                if ($entry == '.' || $entry == '..') { continue; }

                $this->copy("$originFile/$entry", "$targetFile/$entry", true);
            }

            $dir->close();
        }
        else
        {
            parent::copy($originFile, $targetFile, $overwriteNewerFiles);
        }
    }

    /**
     * Strip the current working directory from an absolute path
     *
     * @param  string $path An absolute path
     *
     * @return string
     */
    public function getRelativePath ($path)
    {
        return str_replace(getcwd() . DIRECTORY_SEPARATOR, '', $path);
    }

    /**
     * Get the name of a given file without the extension
     *
     * @param  string $filePath A file path
     *
     * @return string
     */
    public function getBaseName ($filePath)
    {
        return pathinfo($filePath, PATHINFO_FILENAME);
    }

    /**
     * Get the name of a given file
     *
     * @param  string $filePath A file path
     *
     * @return string
     */
    public function getFileName ($filePath)
    {
        return pathinfo($filePath, PATHINFO_BASENAME);
    }

    /**
     * Get the parent directory of a given file
     *
     * @param  string $filePath A file path
     *
     * @return string
     */
    public function getFolderPath ($filePath)
    {
        return pathinfo($filePath, PATHINFO_DIRNAME);
    }

    /**
     * Get the extension of a given file
     *
     * @param  string $filename A file path
     *
     * @return string The extension of the file
     */
    public function getExtension ($filename)
    {
        return pathinfo($filename, PATHINFO_EXTENSION);
    }

    /**
     * @param string $folderPath
     */
    public function isDir ($folderPath)
    {
        return is_dir($folderPath);
    }

    /**
     * Get the full path to the file without the extension
     *
     * @param  string $filename A file path
     *
     * @return string
     */
    public function removeExtension ($filename)
    {
        return $this->appendPath(
                   $this->getFolderPath($filename),
                   $this->getBaseName($filename)
               );
    }

    /**
     * Write a file
     *
     * @param string $targetDir The directory of where the file will be created; the file name is a separate variable
     * @param string $fileName  The name of the file
     * @param string $content   The content that belongs in the file
     *
     * @return SplFileInfo A reference to the newly created file
     */
    public function writeFile ($targetDir, $fileName, $content)
    {
        $outputFolder = $this->getFolderPath($this->absolutePath($targetDir, $fileName));
        $targetFile   = $this->getFileName($fileName);

        if (!file_exists($outputFolder))
        {
            mkdir($outputFolder, 0755, true);
        }

        file_put_contents($this->appendPath($outputFolder, $targetFile), $content, LOCK_EX);

        return (new SplFileInfo(
            $fileName,
            $this->absolutePath($targetDir),
            $this->absolutePath($targetDir, $fileName))
        );
    }
}