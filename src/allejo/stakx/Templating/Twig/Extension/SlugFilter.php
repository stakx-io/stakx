<?php

/**
 * @copyright 2018 Vladimir Jimenez
 * @license   https://github.com/stakx-io/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Templating\Twig\Extension;

use __;

class SlugFilter extends AbstractTwigExtension implements TwigFilterInterface
{
    public function __invoke($str)
    {
        return __::slug($str);
    }

    /**
     * @return \Twig_SimpleFilter
     */
    public static function get()
    {
        return new \Twig_SimpleFilter('slug', new self());
    }
}
