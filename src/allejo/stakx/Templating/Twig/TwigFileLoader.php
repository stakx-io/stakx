<?php

/**
 * @copyright 2018 Vladimir Jimenez
 * @license   https://github.com/stakx-io/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Templating\Twig;

use allejo\stakx\Command\BuildableCommand;
use allejo\stakx\Service;

class TwigFileLoader extends \Twig_Loader_Filesystem
{
    public function getCacheKey($name)
    {
        $path = $this->findTemplate($name);

        if (Service::getParameter(BuildableCommand::WATCHING))
        {
            return $path . filemtime($path);
        }

        return $path;
    }
}
