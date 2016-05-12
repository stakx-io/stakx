<?php

namespace allejo\stakx\Twig;

use allejo\stakx\System\Filesystem;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;
use Twig_Environment;

class FileFunction
{
    public function __invoke(Twig_Environment $env, $filePath)
    {
        $fs    = new Filesystem();
        $globs = $env->getGlobals();
        $dir   = $fs->getFolderPath($globs['__currentTemplate']);
        $path  = $fs->appendPath($dir, $filePath);
        $real  = realpath($path);

        if (strpos($real, getcwd()) !== 0)
        {
            throw new FileNotFoundException(sprintf(
                "The '%s' file could not be found or is outside the website working directory",
                $filePath
            ));
        }

        return file_get_contents($path);
    }

    public static function get ()
    {
        return new \Twig_SimpleFunction('file', new self(), array(
            'needs_environment' => true
        ));
    }
}