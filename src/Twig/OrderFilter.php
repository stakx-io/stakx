<?php

namespace allejo\stakx\Twig;

use allejo\stakx\Object\ContentItem;

class OrderFilter
{
    public function __invoke ($array, $key, $order = "ASC")
    {
        usort($array, function ($a, $b) use ($key, $order)
        {
            $a = ($a instanceof ContentItem) ? $a->getFrontMatter() : $a;
            $b = ($b instanceof ContentItem) ? $b->getFrontMatter() : $b;

            if ($a[$key] == $b[$key])
            {
                return 0;
            }

            if (strtolower($order) === "desc")
            {
                return ($a[$key] < $b[$key]) ? 1 : -1;
            }

            return ($a[$key] > $b[$key]) ? 1 : -1;
        });

        return $array;
    }

    public static function get ()
    {
        return new \Twig_SimpleFilter('order', new self());
    }
}