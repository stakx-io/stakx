<?php declare(strict_types=1);

/**
 * @copyright 2018 Vladimir Jimenez
 * @license   https://github.com/stakx-io/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Templating\Twig\Extension;

use __;
use Twig\TwigFilter;

class SelectFilter extends AbstractTwigExtension implements TwigFilterInterface
{
    public function __invoke($array, $key, $flatten = true, $distinct = true, $ignore_null = true)
    {
        $results = [];

        foreach ($array as $item) {
            $results[] = __::get($item, $key);
        }

        if ($flatten) {
            $results = __::flatten($results);

            if ($distinct) {
                $results = array_values(array_unique($results));
            }
        }

        if ($ignore_null) {
            $results = array_values(array_filter($results));
        }

        return $results;
    }

    public static function get(): TwigFilter
    {
        return new TwigFilter('select', new self());
    }
}
