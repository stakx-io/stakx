<?php

/**
 * @copyright 2018 Vladimir Jimenez
 * @license   https://github.com/stakx-io/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Templating\Twig\Extension;

class Sha1Filter extends AbstractTwigExtension implements TwigFilterInterface
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
