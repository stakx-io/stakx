<?php

namespace allejo\stakx\Event;

use Symfony\Component\EventDispatcher\Event;

class PageViewsCompleted extends Event
{
    const NAME = 'pageviews.completed';
}