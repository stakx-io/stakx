<?php

namespace allejo\stakx\Filesystem;

use allejo\stakx\System\Filesystem;

class FSPath
{
    private $fs;
    private $absolutePath;

    public function __construct($filePath)
    {
        $this->fs = new Filesystem();
        $this->absolutePath = ($this->fs->isAbsolutePath($filePath)) ? $filePath : $this->fs->absolutePath($filePath);
    }

    public function __toString()
    {
        return $this->absolutePath;
    }

    public function isDir()
    {
        return $this->fs->isDir($this->absolutePath);
    }

    public function isFile()
    {
        return $this->fs->isFile($this->absolutePath);
    }

    public function appendToPath($append)
    {
        if ($this->isFile())
        {
            throw new \InvalidArgumentException("Appending to a file's path is not possible");
        }

        $this->absolutePath = $this->buildPath($this->absolutePath, $append, DIRECTORY_SEPARATOR);

        return $this;
    }

    private function buildPath()
    {
        $paths = [];

        foreach (func_get_args() as $arg)
        {
            if ($arg !== '')
            {
                $paths[] = $arg;
            }
        }

        return preg_replace(
            '#(?<!:)' . DIRECTORY_SEPARATOR . '+#',
            DIRECTORY_SEPARATOR,
            join(DIRECTORY_SEPARATOR, $paths)
        );
    }
}
