<?php

namespace allejo\stakx\Twig;

use allejo\stakx\Object\ContentItem;
use Twig_Error_Syntax;

class WhereFilter
{
    public function __invoke ($array, $key, $comparison, $value)
    {
        $results = array();
        $this->search_r($array, $key, $comparison, $value, $results);

        return $results;
    }

    public static function get ()
    {
        return new \Twig_SimpleFilter('where', new self());
    }

    private function search_r ($array, $key, $comparison, $value, &$results)
    {
        if (!is_array($array) && !($array instanceof ContentItem))
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

    private function compare ($array, $key, $comparison, $value)
    {
        $fm = false;

        if ($array instanceof ContentItem)
        {
            $array = $array->getFrontMatter();
            $fm = true;
        }

        if (!isset($array[$key]))
        {
            return ($fm && $comparison === "==" && $value === null);
        }

        switch ($comparison)
        {
            case "==": return ($array[$key] === $value);
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