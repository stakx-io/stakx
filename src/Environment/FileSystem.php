<?php

/**
 * This file contains an extended Filesystem class.
 *
 * This file is part of the Stakx project.
 *
 * @copyright 2016 Vladimir Jimenez
 * @license   https://github.com/allejo/stakx/blob/master/LICENSE.md
 */

namespace allejo\stakx\Environment;

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
    public function relativePath ($pathFragments)
    {
        return implode(DIRECTORY_SEPARATOR, func_get_args());
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

        file_put_contents($this->relativePath($outputFolder, $targetFile), $content, LOCK_EX);

        return (new SplFileInfo(
            $fileName,
            $this->absolutePath($targetDir),
            $this->absolutePath($targetDir, $fileName))
        );
    }
}