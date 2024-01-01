<?php

namespace allejo\stakx\EventSubscriber;

use allejo\stakx\Document\DynamicPageView;
use allejo\stakx\Document\RepeaterPageView;
use allejo\stakx\Document\StaticPageView;
use allejo\stakx\Event\PageViewAdded;
use allejo\stakx\Event\TemplateBridgeConfigured;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class RedirectSubscriber implements EventSubscriberInterface
{
    /** @var array<string, string> */
    private array $urlMap = [];

    public function registerRedirect(PageViewAdded $event): void
    {
        $pageView = $event->getPageView();

        if ($pageView instanceof StaticPageView || $pageView instanceof DynamicPageView)
        {
            $redirects = $pageView->getRedirects();

            foreach ($redirects as $redirect)
            {
                $this->urlMap[$redirect] = $pageView->getPermalink();
            }
        }
        elseif ($pageView instanceof RepeaterPageView)
        {
            $permalinks = $pageView->getRepeaterPermalinks();

            foreach ($pageView->getRepeaterRedirects() as $repeaterRedirect)
            {
                foreach ($repeaterRedirect as $index => $redirect)
                {
                    $this->urlMap[$redirect->getEvaluated()] = $permalinks[$index]->getEvaluated();
                }
            }
        }
    }

    public function registerTemplateBridgeRedirects(TemplateBridgeConfigured $event): void
    {
        $event->getTemplateBridge()->setGlobalVariable('redirects', $this->urlMap);
    }

    public static function getSubscribedEvents(): array
    {
        return [
            PageViewAdded::NAME => 'registerRedirect',
            TemplateBridgeConfigured::NAME => 'registerTemplateBridgeRedirects',
        ];
    }
}
