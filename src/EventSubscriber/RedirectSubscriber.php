<?php

namespace allejo\stakx\EventSubscriber;

use allejo\stakx\Document\DynamicPageView;
use allejo\stakx\Document\RepeaterPageView;
use allejo\stakx\Document\StaticPageView;
use allejo\stakx\Event\PageViewAdded;
use allejo\stakx\RedirectMapper;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class RedirectSubscriber implements EventSubscriberInterface
{
    /** @var RedirectMapper */
    private $redirectMapper;

    public function __construct(RedirectMapper $redirectMapper)
    {
        $this->redirectMapper = $redirectMapper;
    }

    public function registerRedirect(PageViewAdded $event)
    {
        $pageView = $event->getPageView();

        if ($pageView instanceof StaticPageView || $pageView instanceof DynamicPageView)
        {
            $redirects = $pageView->getRedirects();

            foreach ($redirects as $redirect)
            {
                $this->redirectMapper->registerRedirect($redirect, $pageView->getPermalink());
            }
        }
        elseif ($pageView instanceof RepeaterPageView)
        {
            $permalinks = $pageView->getRepeaterPermalinks();

            foreach ($pageView->getRepeaterRedirects() as $repeaterRedirect)
            {
                foreach ($repeaterRedirect as $index => $redirect)
                {
                    $this->redirectMapper->registerRedirect(
                        $redirect->getEvaluated(),
                        $permalinks[$index]->getEvaluated()
                    );
                }
            }
        }
    }

    /**
     * @inheritDoc
     */
    public static function getSubscribedEvents()
    {
        return [
            PageViewAdded::NAME => 'registerRedirect',
        ];
    }
}
