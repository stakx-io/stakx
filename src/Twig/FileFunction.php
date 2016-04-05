<?php

namespace allejo\stakx\Twig;

use Symfony\Component\Finder\Finder;

class FileFunction
{
    public static function get ()
    {
        return new \Twig_SimpleFunction('finder', new Finder());
    }
}