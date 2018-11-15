<?php

/**
 * @copyright 2018 Vladimir Jimenez
 * @license   https://github.com/stakx-io/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Templating\Twig;

use allejo\stakx\RuntimeStatus;
use allejo\stakx\Service;

class TwigFileLoader extends \Twig_Loader_Filesystem
{
    public function getCacheKey($name)
    {
        $template = $this->findTemplate($name);

        if (Service::hasRunTimeFlag(RuntimeStatus::IN_SERVE_MODE))
        {
            return sprintf('%s_%s', $template, filemtime($template));
        }

        return $template;
    }
}
