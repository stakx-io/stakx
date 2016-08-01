<?php

namespace allejo\stakx\Twig;

use allejo\stakx\System\Filesystem;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;
use Twig_Environment;

abstract class TwigFilesystem
{
    protected $fs;
    protected $globs;
    protected $dir;
    protected $path;

    public function __invoke(Twig_Environment $env, $location)
    {
        $this->fs    = new Filesystem();
        $this->globs = $env->getGlobals();
        $this->dir   = $this->fs->getFolderPath($this->globs['__currentTemplate']);
        $this->path  = $this->fs->appendPath($this->dir, $location);

        if (is_file($this->path))
        {
            $this->path = realpath($this->path);
        }

        if (strpos($this->path, getcwd()) !== 0)
        {
            throw new FileNotFoundException(sprintf(
                "The '%s' file could not be found or is outside the website working directory",
                $location
            ));
        }
    }
}