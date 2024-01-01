<?php

/**
 * @copyright 2018 Vladimir Jimenez
 * @license   https://github.com/stakx-io/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Event;

use allejo\stakx\Compilation\Configuration;
use Symfony\Contracts\EventDispatcher\Event;

class ConfigurationParseComplete extends Event
{
    public const NAME = 'configuration.parse.complete';

    public function __construct(private readonly Configuration $configuration)
    {
    }

    public function getConfiguration(): Configuration
    {
        return $this->configuration;
    }
}
