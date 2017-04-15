<?php

/**
 * @copyright 2017 Vladimir Jimenez
 * @license   https://github.com/allejo/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Twig;

use allejo\stakx\Command\BuildableCommand;
use allejo\stakx\Service;

class StakxTwigFileLoader extends \Twig_Loader_Filesystem
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
