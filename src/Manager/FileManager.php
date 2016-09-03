<?php

namespace allejo\stakx\Manager;

use allejo\stakx\System\Folder;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

abstract class FileManager extends BaseManager
{
    /**
     * @var Folder
     */
    protected $outputDirectory;

    /**
     * @var Finder
     */
    protected $finder;

    /**
     * @var string[]
     */
    protected $files;

    /**
     * @param Folder $directory
     */
    public function setFolder ($directory)
    {
        $this->outputDirectory = $directory;
    }

    /**
     * @param SplFileInfo $file   The relative path of the file to be copied
     * @param string      $prefix
     */
    protected function copyToCompiledSite ($file, $prefix = "")
    {
        if (!$this->fs->exists($file)) { return; }

        $filePath = $file->getRealPath();
        $pathToStrip = $this->fs->appendPath(getcwd(), $prefix);
        $siteTargetPath = ltrim(str_replace($pathToStrip, "", $filePath), DIRECTORY_SEPARATOR);

        try
        {
            $this->outputDirectory->copyFile($filePath, $siteTargetPath);
        }
        catch (\Exception $e)
        {
            $this->output->error($e->getMessage());
        }
    }

    abstract public function copyFiles ();
}