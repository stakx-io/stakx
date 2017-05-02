<?php

/**
 * @copyright 2017 Vladimir Jimenez
 * @license   https://github.com/allejo/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Utilities;

abstract class ArrayUtilities
{
    public static function is_multidimensional(array &$array)
    {
        foreach ($array as &$element)
        {
            if (is_array($element))
            {
                return true;
            }
        }

        return false;
    }

    public static function array_can_be_indexed(array &$arr, $indexKey)
    {
        if (count($arr) >= 1)
        {
            return isset($arr[0][$indexKey]);
        }

        return false;
    }

    public static function array_index_by_key(array &$arr, $indexKey)
    {
        $result = array();

        foreach ($arr as &$value)
        {
            if (isset($value[$indexKey]))
            {
                $result[$value[$indexKey]] = $value;
            }
        }

        return $result;
    }

    public static function array_merge_defaults(array &$arr1, array &$arr2, $indexKey)
    {
        $merged = $arr1;

        foreach ($arr2 as $key => &$value)
        {
            if (is_array($value) && isset($arr1[$key]))
            {
                if (self::array_can_be_indexed($value, $indexKey))
                {
                    $indexedArr1 = self::array_index_by_key($arr1[$key], $indexKey);
                    $indexedArr2 = self::array_index_by_key($value, $indexKey);
                    $merged[$key] = array_merge($indexedArr1, $indexedArr2);
                }
                else
                {
                    $merged[$key] = array_merge($arr1[$key], $value);
                }

                continue;
            }

            $merged[$key] = $value;
        }

        return $merged;
    }

    /**
     * @param  string $key
     * @param  array  $array
     * @param  bool   $considerOffset
     *
     * @return array
     */
    public static function associative_array_split($key, array &$array, $considerOffset = true)
    {
        $offset = array_search($key, array_keys($array)) + (int)$considerOffset;
        $result = array();

        $result[0] = array_slice($array, 0, $offset, true);
        $result[1] = array_slice($array, $offset, null, true);

        return $result;
    }
}
