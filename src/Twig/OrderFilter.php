<?php

namespace allejo\stakx\Twig;

class OrderFilter
{
    public function __invoke ($array, $key, $order = "ASC")
    {
        usort($array, function ($a, $b) use ($key, $order) {
            if ($a[$key] == $b[$key]) return 0;

            if (strtolower($order) === "desc") {
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