<?php

namespace allejo\stakx\Twig;

class GroupByFilter
{
    public function __invoke($array, $sortKey)
    {
        $arr = array();

        foreach($array as $key => $item)
        {
            $arr[$item[$sortKey]][$key] = $item;
        }


        return $arr;
    }

    public static function get()
    {
        return new \Twig_SimpleFilter('group', new self());
    }
}