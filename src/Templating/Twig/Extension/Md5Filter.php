<?php declare(strict_types=1);

/**
 * @copyright 2018 Vladimir Jimenez
 * @license   https://github.com/stakx-io/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Templating\Twig\Extension;

use Twig\TwigFilter;

class Md5Filter extends AbstractTwigExtension implements TwigFilterInterface
{
    public function __invoke($str)
    {
        return md5((string)$str);
    }

    public static function get(): TwigFilter
    {
        return new TwigFilter('md5', new self());
    }
}
