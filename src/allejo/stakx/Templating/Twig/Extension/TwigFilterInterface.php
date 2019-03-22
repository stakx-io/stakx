<?php

/**
 * @copyright 2018 Vladimir Jimenez
 * @license   https://github.com/stakx-io/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Templating\Twig\Extension;

use Twig\TwigFilter;

interface TwigFilterInterface
{
    const CONTAINER_TAG = 'stakx.twig_filter';

    /**
     * @return TwigFilter
     */
    public static function get();
}
