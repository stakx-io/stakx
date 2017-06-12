<?php

/**
 * @copyright 2017 Vladimir Jimenez
 * @license   https://github.com/allejo/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Twig;

class Md5Filter implements StakxTwigFilter
{
    public function __invoke($str)
    {
        return md5($str);
    }

    /**
     * @return \Twig_SimpleFilter
     */
    public static function get()
    {
        return new \Twig_SimpleFilter('md5', new self());
    }
}
