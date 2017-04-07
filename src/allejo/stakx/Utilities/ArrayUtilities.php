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
        foreach ($array as $element)
        {
            if (is_array($element))
            {
                return true;
            }
        }

        return false;
    }

    public static function array_merge_defaults(array &$array1, array &$array2, $keyField)
    {
        $merged = $array1;

        foreach ($array2 as $key => &$value)
        {
            $valueMerged = false;

            foreach ($merged as $mergedKey => &$item)
            {
                if (is_array($item) && array_key_exists($keyField, $item) && $item[$keyField] == $value[$keyField])
                {
                    $item = array_merge($item, $value);
                    $valueMerged = true;

                    break;
                }
                elseif ($mergedKey == $key)
                {
                    if (is_numeric($mergedKey))
                    {
                        $merged[] = $value;
                    }
                    elseif (is_array($item))
                    {
                        $item = array_unique(array_merge($item, $value));
                    }
                    else
                    {
                        $item = $value;
                    }

                    $valueMerged = true;

                    break;
                }
            }

            if (!$valueMerged)
            {
                $merged[$key] = $value;
            }
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

        $result[0] = array_slice($array, 0 , $offset, true);
        $result[1] = array_slice($array, $offset, null, true);

        return $result;
    }
}
