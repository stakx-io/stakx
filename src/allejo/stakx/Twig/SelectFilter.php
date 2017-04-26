<?php

/**
 * @copyright 2017 Vladimir Jimenez
 * @license   https://github.com/allejo/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Twig;

class SelectFilter
{
    public function __invoke($array, $key, $flatten = true, $distinct = true)
    {
        $results = array();

        foreach ($array as $item)
        {
            if (!is_array($item) && !($item instanceof \ArrayAccess))
            {
                continue;
            }

            if (isset($item[$key]))
            {
                $results[] = $item[$key];
            }
        }

        if ($flatten)
        {
            $results = self::flatten($results);

            if ($distinct)
            {
                $distinct = array();

                foreach ($results as $key => $result)
                {
                    $distinct[$result] = true;
                }

                $results = array_keys($distinct);
            }
        }

        return $results;
    }

    public static function get()
    {
        return new \Twig_SimpleFilter('select', new self());
    }

    private static function flatten(array $array)
    {
        $return = array();
        array_walk_recursive($array, function($a) use (&$return) { $return[] = $a; });
        return $return;
    }
}