<?php

/**
 * @copyright 2018 Vladimir Jimenez
 * @license   https://github.com/stakx-io/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Console\Command;

use allejo\stakx\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\DependencyInjection\Container;

abstract class ContainerAwareCommand extends Command
{
    private ?Container $container = null;

    /**
     * @throws \LogicException
     */
    public function getContainer(): Container
    {
        if ($this->container === null)
        {
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
