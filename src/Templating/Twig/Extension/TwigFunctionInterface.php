<?php

/**
 * @copyright 2018 Vladimir Jimenez
 * @license   https://github.com/stakx-io/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Templating\Twig\Extension;

use Twig\TwigFunction;

interface TwigFunctionInterface
{
    const CONTAINER_TAG = 'stakx.twig_function';

    /**
     * @return TwigFunction
     */
    public static function get();
}
