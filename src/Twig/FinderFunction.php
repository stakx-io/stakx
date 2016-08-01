<?php

namespace allejo\stakx\Twig;

use allejo\stakx\System\Filesystem;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;
use Symfony\Component\Finder\Finder;
use Twig_Environment;

class FinderFunction
{
    public function __invoke(Twig_Environment $env, $folderLocation)
    {
        $fs    = new Filesystem();
        $globs = $env->getGlobals();
        $dir   = $fs->getFolderPath($globs['__currentTemplate']);
        $path  = $fs->appendPath($dir, $folderLocation);

        if (strpos($path, getcwd()) !== 0)
        {
            throw new FileNotFoundException(sprintf(
                "The '%s' file could not be found or is outside the website working directory",
                $folderLocation
            ));
        }

        $finder = new Finder();
        $finder->in($folderLocation);

        return $finder;
    }

    public static function get ()
    {
        return new \Twig_SimpleFunction('finder', new self(), array(
            'needs_environment' => true
        ));
    }
}