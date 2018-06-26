<?php

/**
 * @copyright 2018 Vladimir Jimenez
 * @license   https://github.com/stakx-io/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Utilities;

abstract class ArrayUtilities
{
    /**
     * @param array $array
     *
     * @return bool
     */
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

    /**
     * Check whether the elements of a multidimensional array can be indexed based on a key.
     *
     * In the following example, the array can be indexed with the 'name' key.
     *
     * ```php
     * array(
     *     array('name' => 'sample 1'),
     *     array('name' => 'sample 2'),
     *     array('name' => 'sample 3'),
     * )
     * ```
     *
     * @param array  $arr
     * @param string $indexKey The key to consider the index
     *
     * @return bool
     */
    public static function array_can_be_indexed(array &$arr, $indexKey)
    {
        foreach ($arr as &$value)
        {
            if (isset($value[$indexKey]) && is_scalar($value[$indexKey]))
            {
                return true;
            }
        }

        return false;
    }

    /**
     * Index an array passed on given key.
     *
     * The resulting array would be as follows when indexed by the 'name' key.
     *
     * ```php
     * array(
     *     'sample 1' => array('name' => 'sample 1'),
     *     'sample 2' => array('name' => 'sample 2'),
     *     'sample 3' => array('name' => 'sample 3'),
     * )
     * ```
     *
     * @param array  $arr
     * @param string $indexKey
     *
     * @see    ArrayUtilities::array_can_be_indexed()
     *
     * @return array The original array but each element's key will be the value of the element's key value
     */
    public static function array_index_by_key(array &$arr, $indexKey)
    {
        $result = [];

        foreach ($arr as &$value)
        {
            if (isset($value[$indexKey]) && is_scalar($value[$indexKey]))
            {
                $result[$value[$indexKey]] = $value;
            }
        }

        return $result;
    }

    /**
     * Merge two arrays together while respecting possible indexing.
     *
     * @param array  $arr1     The first array
     * @param array  $arr2     The values in this array will overwrite the respective values from $arr2
     * @param string $indexKey The key to respect when indexing
     *
     * @see    ArrayUtilities::array_index_by_key()
     *
     * @return array
     */
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
     * Split an associative array into two chunks based on the position of a key.
     *
     * @param string $key       The key we'll be using as the splitting point
     * @param array  $array     The array we're splitting up
     * @param bool   $inclusive when set to true, the key being used as the splitting point will be incorporated in the
     *                          first chunk of the split
     *
     * @return array An array with two indices (0 & 1) containing the respective chunks of the array
     */
    public static function associative_array_split($key, array &$array, $inclusive = true)
    {
        $offset = array_search($key, array_keys($array)) + (int)$inclusive;
        $result = [];

        $result[0] = array_slice($array, 0, $offset, true);
        $result[1] = array_slice($array, $offset, null, true);

        return $result;
    }
}
