<?php

/**
 * @copyright 2017 Vladimir Jimenez
 * @license   https://github.com/allejo/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Twig;

use Twig_Environment;

class FileFunction extends TwigFilesystem
{
    public function __invoke(Twig_Environment $env, $filePath)
    {
        parent::__invoke($env, $filePath);

        return file_get_contents($this->path);
    }

    public static function get()
    {
        return new \Twig_SimpleFunction('file', new self(), array(
            'needs_environment' => true,
        ));
    }
}
