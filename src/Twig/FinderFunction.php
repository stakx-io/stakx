<?php

namespace allejo\stakx\Twig;

use Symfony\Component\Finder\Finder;

class FinderFunction
{
    public static function get ()
    {
        return new \Twig_SimpleFunction('finder', new Finder());
    }
}