<?php

/**
 * @copyright 2018 Vladimir Jimenez
 * @license   https://github.com/stakx-io/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Event;

use Symfony\Component\EventDispatcher\Event;

class PageViewsCompleted extends Event
{
    const NAME = 'pageviews.completed';
}
