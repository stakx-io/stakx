<?php

/**
 * @copyright 2018 Vladimir Jimenez
 * @license   https://github.com/stakx-io/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Event;

use Symfony\Contracts\EventDispatcher\Event;

/**
 * A notification-only event that is fired whenever the website finishes building.
 *
 * @since 0.2.0
 */
class BuildProcessComplete extends Event
{
    public const NAME = 'build.process.complete';

    public function __construct()
    {
    }
}
