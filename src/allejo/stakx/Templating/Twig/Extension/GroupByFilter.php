<?php

/**
 * @copyright 2018 Vladimir Jimenez
 * @license   https://github.com/stakx-io/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Templating\Twig\Extension;

class GroupByFilter extends AbstractTwigExtension implements TwigFilterInterface
{
    public function __invoke($array, $sortKey)
    {
        $arr = [];

        foreach ($array as $key => $item)
        {
            if (!isset($item[$sortKey]))
            {
                continue;
            }

            $groupBy = $item[$sortKey];

            if (is_bool($groupBy))
            {
                $groupBy = ($groupBy) ? 'true' : 'false';
            }
            elseif (!is_scalar($groupBy))
            {
                trigger_error('You cannot group by a non-scalar value', E_USER_WARNING);
                continue;
            }

            $arr[$groupBy][$key] = $item;
        }

        return $arr;
    }

    public static function get()
    {
        return new \Twig_SimpleFilter('group', new self());
    }
}
