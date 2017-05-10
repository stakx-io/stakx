<?php

/**
 * @copyright 2017 Vladimir Jimenez
 * @license   https://github.com/allejo/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Twig;

interface StakxTwigFilter
{
    /**
     * @return \Twig_SimpleFilter
     */
    public static function get();
}