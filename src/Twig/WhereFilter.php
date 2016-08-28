<?php

namespace allejo\stakx\Twig;

use allejo\stakx\Object\ContentItem;

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
        $array = ($array instanceof ContentItem) ? $array->getFrontMatter() : $array;

        if ($comparison === "==")
        {
            return (isset($array[$key]) && $array[$key] === $value);
        }
        elseif ($comparison === "!=")
        {
            return (isset($array[$key]) && $array[$key] !== $value);
        }
        elseif ($comparison === ">")
        {
            return (isset($array[$key]) && $array[$key] > $value);
        }
        elseif ($comparison === ">=")
        {
            return (isset($array[$key]) && $array[$key] >= $value);
        }
        elseif ($comparison === "<")
        {
            return (isset($array[$key]) && $array[$key] < $value);
        }
        elseif ($comparison === "<=")
        {
            return (isset($array[$key]) && $array[$key] <= $value);
        }

        return false;
    }
}