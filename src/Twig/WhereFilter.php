<?php

namespace allejo\stakx\Twig;

use allejo\stakx\Object\FrontMatterObject;
use allejo\stakx\Object\JailObject;
use Twig_Error_Syntax;

class WhereFilter
{
    /**
     * @param  array|\ArrayAccess[] $array      The elements to filter through
     * @param  string               $key        The key value in an associative array or FrontMatter
     * @param  string               $comparison The actual comparison symbols being used
     * @param  mixed                $value      The value we're searching for
     *
     * @return array
     */
    public function __invoke ($array, $key, $comparison, $value)
    {
        $results = array();
        $this->search_r($array, $key, $comparison, $value, $results);

        return $results;
    }

    /**
     * @return \Twig_SimpleFilter
     */
    public static function get ()
    {
        return new \Twig_SimpleFilter('where', new self());
    }

    /**
     * Recursive searching calling our comparison
     *
     * @param array|\ArrayAccess[] $array      The elements to filter through
     * @param string               $key        The key value in an associative array or FrontMatter
     * @param string               $comparison The actual comparison symbols being used
     * @param string               $value      The value we're searching for
     * @param array                $results    The reference to where to keep the filtered elements
     */
    private function search_r ($array, $key, $comparison, $value, &$results)
    {
        if (!is_array($array) && !($array instanceof \ArrayAccess))
        {
            return;
        }

        if ($this->compare($array, $key, $comparison, $value))
        {
            $results[] = $array;
        }

        foreach ($array as $subarray)
        {
            $this->search_r($subarray, $key, $comparison, $value, $results);
        }
    }

    /**
     * The logic for determining if an element matches the filter
     *
     * @param  array|\ArrayAccess[] $array      The elements to filter through
     * @param  string               $key        The key value in an associative array or FrontMatter
     * @param  string               $comparison The actual comparison symbols being used
     * @param  mixed                $value      The value we're searching for
     *
     * @return bool
     *
     * @throws Twig_Error_Syntax
     */
    private function compare ($array, $key, $comparison, $value)
    {
        if (!isset($array[$key]) &&
            !($array instanceof JailObject && $array->coreInstanceOf(FrontMatterObject::class) && $comparison == '==' && is_null($value)))
        {
            return false;
        }

        switch ($comparison)
        {
            case "==": return ((is_null($value) && !isset($array[$key])) || $array[$key] === $value);
            case "!=": return ($array[$key] !== $value);
            case ">" : return ($array[$key] > $value);
            case ">=": return ($array[$key] >= $value);
            case "<" : return ($array[$key] < $value);
            case "<=": return ($array[$key] <= $value);
            case "~=": return ($this->contains($array[$key], $value));

            default:
                throw new Twig_Error_Syntax("Invalid where comparison ({$comparison})");
        }
    }

    private function contains ($haystack, $needle)
    {
        return ((is_array($haystack) && in_array($needle, $haystack)) ||
                (is_string($haystack) && strpos($haystack, $needle) !== false));
    }
}