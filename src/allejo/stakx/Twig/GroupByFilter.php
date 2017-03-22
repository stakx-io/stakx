<?php

/**
 * @copyright 2017 Vladimir Jimenez
 * @license   https://github.com/allejo/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Twig;

class GroupByFilter
{
    public function __invoke($array, $sortKey)
    {
        $arr = array();

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
