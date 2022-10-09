<?php declare(strict_types=1);

/**
 * @copyright 2018 Vladimir Jimenez
 * @license   https://github.com/stakx-io/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Event;

use allejo\stakx\Configuration;
use Symfony\Contracts\EventDispatcher\Event;

class ConfigurationParseComplete extends Event
{
    final public const NAME = 'configuration.parse.complete';

    public function __construct(private readonly Configuration $configuration)
    {
    }

    public function getConfiguration()
    {
        return $this->configuration;
    }
}
