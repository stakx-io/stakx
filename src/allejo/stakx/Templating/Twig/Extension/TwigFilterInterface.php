<?php

/**
 * @copyright 2018 Vladimir Jimenez
 * @license   https://github.com/stakx-io/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Templating\Twig\Extension;

interface TwigFilterInterface
{
    const CONTAINER_TAG = 'stakx.twig_filter';

    /**
     * @return \Twig_SimpleFilter
     */
    public static function get();
}
