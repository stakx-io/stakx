<?php

/**
 * @copyright 2017 Vladimir Jimenez
 * @license   https://github.com/allejo/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Command;

use allejo\stakx\Core\Application;
use Symfony\Component\Console\Command\Command;

abstract class ContainerAwareCommand extends Command
{
    private $container;

    public function getContainer()
    {
        if ($this->container === null)
        {
            /** @var Application $application */
            $application = $this->getApplication();

            if ($application === null)
            {
                throw new \LogicException('The container cannot be retrieved as the application instance is not yet set.');
            }

            $this->container = $application->getContainer();
        }

        return $this->container;
    }
}