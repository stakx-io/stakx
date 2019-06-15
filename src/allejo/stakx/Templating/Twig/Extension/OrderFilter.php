<?php

/**
 * @copyright 2018 Vladimir Jimenez
 * @license   https://github.com/stakx-io/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Templating\Twig\Extension;

use __;
use Twig\TwigFilter;

class OrderFilter extends AbstractTwigExtension implements TwigFilterInterface
{
    public function __invoke($array, $key, $order = 'ASC', $case_insensitive = false)
    {
        if (!is_array($array))
        {
            return $array;
        }

        usort($array, function ($a, $b) use ($key, $order, $case_insensitive) {
            $aValue = __::get($a, $key);
            $bValue = __::get($b, $key);

            if ($case_insensitive)
            {
                $aValue = strtolower($aValue);
                $bValue = strtolower($bValue);
            }

            if ($aValue == $bValue)
            {
                return 0;
            }

            if (strtolower($order) === 'desc')
            {
                return ($aValue < $bValue) ? 1 : -1;
            }

            return ($aValue > $bValue) ? 1 : -1;
        });

        return $array;
    }

    public static function get()
    {
        return new TwigFilter('order', new self());
    }
}
