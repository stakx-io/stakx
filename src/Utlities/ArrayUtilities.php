<?php

namespace allejo\stakx\Utilities;

abstract class ArrayUtilities
{
    public static function array_merge_defaults (array &$array1, array &$array2, $keyField)
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
                else if ($mergedKey == $key)
                {
                    if (is_numeric($mergedKey))
                    {
                        $merged[] = $value;
                    }
                    else if (is_array($item))
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
}