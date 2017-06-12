<?php

/**
 * @copyright 2017 Vladimir Jimenez
 * @license   https://github.com/allejo/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Twig;

class Sha1Filter implements StakxTwigFilter
{
    public function __invoke($str)
    {
        return sha1($str);
    }

    /**
     * @return \Twig_SimpleFilter
     */
    public static function get()
    {
        return new \Twig_SimpleFilter('sha1', new self());
    }
}
