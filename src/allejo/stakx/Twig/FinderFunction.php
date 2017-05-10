<?php

/**
 * @copyright 2017 Vladimir Jimenez
 * @license   https://github.com/allejo/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Twig;

use Symfony\Component\Finder\Finder;
use Twig_Environment;

class FinderFunction extends TwigFilesystem implements StakxTwigFunction
{
    public function __invoke(Twig_Environment $env, $folderLocation)
    {
        parent::__invoke($env, $folderLocation);

        $finder = new Finder();
        $finder->in($folderLocation);

        return $finder;
    }

    public static function get()
    {
        return new \Twig_SimpleFunction('finder', new self(), array(
            'needs_environment' => true,
        ));
    }
}
