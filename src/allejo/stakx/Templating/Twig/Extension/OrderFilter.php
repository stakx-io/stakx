<?php

/**
 * @copyright 2018 Vladimir Jimenez
 * @license   https://github.com/stakx-io/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Templating\Twig\Extension;

class OrderFilter extends AbstractTwigExtension implements TwigFilterInterface
{
    public function __invoke($array, $key, $order = 'ASC')
    {
        if (!is_array($array))
        {
            return $array;
        }

        usort($array, function ($a, $b) use ($key, $order) {
            if ($a[$key] == $b[$key])
            {
                return 0;
            }

            if (strtolower($order) === 'desc')
            {
                return ($a[$key] < $b[$key]) ? 1 : -1;
            }

            return ($a[$key] > $b[$key]) ? 1 : -1;
        });

        return $array;
    }

    public static function get()
    {
        return new \Twig_SimpleFilter('order', new self());
    }
}
