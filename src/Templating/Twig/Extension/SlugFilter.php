<?php declare(strict_types=1);

/**
 * @copyright 2018 Vladimir Jimenez
 * @license   https://github.com/stakx-io/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Templating\Twig\Extension;

use __;
use Twig\TwigFilter;

class SlugFilter extends AbstractTwigExtension implements TwigFilterInterface
{
    public function __invoke($str)
    {
        return __::slug($str);
    }

    public static function get(): TwigFilter
    {
        return new TwigFilter('slug', new self());
    }
}
