<?php

/**
 * @copyright 2017 Vladimir Jimenez
 * @license   https://github.com/allejo/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Twig;

interface StakxTwigFunction
{
    /**
     * @return \Twig_SimpleFunction
     */
    public static function get();
}