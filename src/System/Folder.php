<?php

namespace allejo\stakx\System;

use Symfony\Component\Filesystem\Exception\FileNotFoundException;
use Symfony\Component\Finder\SplFileInfo;

/**
 * All folder paths stored inside of this class will **not** have the ending DIRECTORY_SEPARATOR
 *
 * @package allejo\stakx\System
 */
class Folder
{
    protected $fs;
    protected $absolutePath;
    protected $targetDirectories;

    public function __construct ($folderPath)
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

    public function __toString ()
    {
        return rtrim($this->absolutePath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
    }

    public function setTargetDirectory ($folderName)
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
     * @param string $absolutePath
     * @param string $targetPath
     */
    public function copyFile ($absolutePath, $targetPath)
    {
        $targetPath = ltrim($targetPath, DIRECTORY_SEPARATOR);

        $this->fs->copy(
            $absolutePath,
            $this->buildPath($this->getCwd(), $targetPath),
            true
        );
    }

    /**
     * @param string $targetPath
     * @param string $fileContent
     */
    public function writeFile ($targetPath, $fileContent)
    {
        $outputFolder   = $this->fs->getFolderPath($targetPath);
        $targetFileName = $this->fs->getFileName($outputFolder);

        $absoluteFolderPath = $this->buildPath($this->getCwd(), $outputFolder);

        if (!file_exists($absoluteFolderPath))
        {
            mkdir($absoluteFolderPath, 0755, true);
        }

        file_put_contents(
            $this->buildPath($this->getCwd(), $targetPath),
            $fileContent,
            LOCK_EX
        );

        return (new SplFileInfo(
            $targetFileName,
            $absoluteFolderPath,
            $this->buildPath($absoluteFolderPath, $targetFileName)
        ));
    }

    /**
     * @param string $pathFragments
     */
    private function buildPath ($pathFragments)
    {
        $paths = func_get_args();

        return implode(DIRECTORY_SEPARATOR, $paths);
    }

    /**
     * Returns the absolute path of where files will be placed
     *
     * @return string
     */
    private function getCwd ()
    {
        $location = array_merge(array($this->absolutePath), $this->targetDirectories);

        return implode(DIRECTORY_SEPARATOR, $location);
    }
}