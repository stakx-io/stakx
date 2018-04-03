<?php

/**
 * @copyright 2018 Vladimir Jimenez
 * @license   https://github.com/allejo/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Templating\Twig\Extension;

interface TwigFunctionInterface
{
    const CONTAINER_TAG = 'stakx.twig_function';

    /**
     * @return \Twig_SimpleFunction
     */
    public static function get();
}
