<?php

/**
 * @copyright 2018 Vladimir Jimenez
 * @license   https://github.com/stakx-io/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Templating\Twig\Extension;

use allejo\stakx\Filesystem\FilesystemLoader as fs;
use allejo\stakx\Service;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;
use Twig_Environment;

abstract class AbstractFilesystemTwigExtension extends AbstractTwigExtension
{
    protected $globs;
    protected $dir;
    protected $path;

    public function __invoke(Twig_Environment $env, $location)
    {
        $this->globs = $env->getGlobals();
        $this->dir = fs::getFolderPath($this->globs['__currentTemplate']);
        $this->path = fs::appendPath($this->dir, $location);

        if (is_file($this->path))
        {
            $this->path = realpath($this->path);
        }

        if (strpos($this->path, Service::getWorkingDirectory()) !== 0)
        {
            throw new FileNotFoundException(sprintf(
                "The '%s' file could not be found or is outside the website working directory",
                $location
            ));
        }
    }
}
