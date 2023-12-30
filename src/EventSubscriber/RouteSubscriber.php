<?php

/**
 * @copyright 2018 Vladimir Jimenez
 * @license   https://github.com/stakx-io/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\EventSubscriber;

use allejo\stakx\Event\PageViewAdded;
use allejo\stakx\Server\RouteMapper;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class RouteSubscriber implements EventSubscriberInterface
{
    public function __construct(private readonly RouteMapper $routerMapping)
    {
    }

    public function registerPageView(PageViewAdded $event): void
    {
        $this->routerMapping->registerPageView($event->getPageView());
    }

    public static function getSubscribedEvents(): array
    {
        return [
            PageViewAdded::NAME => 'registerPageView',
        ];
    }
}
