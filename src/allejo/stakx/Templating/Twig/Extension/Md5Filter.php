<?php

/**
 * @copyright 2018 Vladimir Jimenez
 * @license   https://github.com/stakx-io/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Templating\Twig\Extension;

use Twig\TwigFilter;

class Md5Filter extends AbstractTwigExtension implements TwigFilterInterface
{
    public function __invoke($str)
    {
        return md5($str);
    }

    /**
     * @return TwigFilter
     */
    public static function get()
    {
        return new TwigFilter('md5', new self());
    }
}
