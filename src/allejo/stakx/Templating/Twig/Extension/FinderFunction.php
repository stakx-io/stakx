<?php

/**
 * @copyright 2018 Vladimir Jimenez
 * @license   https://github.com/stakx-io/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Templating\Twig\Extension;

use allejo\stakx\Filesystem\FilesystemLoader as fs;
use Symfony\Component\Finder\Finder;

class FinderFunction extends AbstractFilesystemTwigExtension implements TwigFunctionInterface
{
    public function __invoke($folderLocation)
    {
        parent::__invoke($folderLocation);

        $finder = new Finder();
        $finder->in(fs::absolutePath($folderLocation));

        return $finder;
    }

    public static function get()
    {
        return new \Twig_SimpleFunction('finder', new self());
    }

    public static function disableInSafeMode()
    {
        return true;
    }
}
