<?php

namespace allejo\stakx\Manager;

use Symfony\Component\Finder\SplFileInfo;

class AssetManager extends FileManager
{
    public function __construct($includes = array(), $excludes = array())
    {
        parent::__construct();

        $this->finder = $this->fs->getFinder($includes, $excludes);
    }

    public function copyFiles()
    {
        /** @var $file SplFileInfo */
        foreach ($this->finder as $file)
        {
            if ($this->tracking)
            {
                $this->files[$file->getRelativePathname()] = $file;
            }

            $this->copyToCompiledSite($file);
        }
    }
}