<?php declare(strict_types=1);

/**
 * @copyright 2018 Vladimir Jimenez
 * @license   https://github.com/stakx-io/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Templating\Twig\Extension;

use Twig\TwigFilter;

interface TwigFilterInterface
{
    public const CONTAINER_TAG = 'stakx.twig_filter';

    public static function get(): TwigFilter;
}
