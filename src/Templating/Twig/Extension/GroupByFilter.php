<?php

/**
 * @copyright 2018 Vladimir Jimenez
 * @license   https://github.com/stakx-io/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Templating\Twig\Extension;

use __;
use Twig\TwigFilter;

class GroupByFilter extends AbstractTwigExtension implements TwigFilterInterface
{
    public function __invoke($array, $sortKey)
    {
        $arr = [];

        foreach ($array as $key => $item)
        {
            $groupBy = __::get($item, $sortKey);

            if ($groupBy === null)
            {
                continue;
            }

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
        return new TwigFilter('group', new self());
    }
}
