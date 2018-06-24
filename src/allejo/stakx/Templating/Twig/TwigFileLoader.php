<?php

/**
 * @copyright 2018 Vladimir Jimenez
 * @license   https://github.com/stakx-io/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Templating\Twig;

class TwigFileLoader extends \Twig_Loader_Filesystem
{
    public function getCacheKey($name)
    {
        return $this->findTemplate($name);
    }
}
