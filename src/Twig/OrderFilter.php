<?php

namespace allejo\stakx\Twig;

use allejo\stakx\Object\FrontMatterObject;

class OrderFilter
{
    public function __invoke ($array, $key, $order = "ASC")
    {
        if (!is_array($array))
        {
            return $array;
        }

        usort($array, function ($a, $b) use ($key, $order)
        {
            $a = ($a instanceof FrontMatterObject) ? $a->getFrontMatter() : $a;
            $b = ($b instanceof FrontMatterObject) ? $b->getFrontMatter() : $b;

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