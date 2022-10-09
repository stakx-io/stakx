<?php

namespace allejo\stakx\Event;

use allejo\stakx\Document\BasePageView;
use Symfony\Component\EventDispatcher\Event;

/**
 * This event is fired before a redirect is created. This event provides read-only access to the parent PageView and
 * write access to the PageView generated to write a flat redirect file.
 *
 * @since 0.2.1
 */
class RedirectPreOutput extends Event
{
    const NAME = 'redirect.preoutput';

    /** @var string */
    private $fromUrl;

    /** @var string */
    private $toUrl;

    /** @var BasePageView */
    private $parentPageView;

    /** @var BasePageView */
    private $redirectPageView;

    /**
     * @param string       $from
     * @param string       $to
     * @param BasePageView $pageView
     * @param BasePageView $redirectPageView
     */
    public function __construct($from, $to, $pageView, $redirectPageView)
    {
        $this->fromUrl = $from;
        $this->toUrl = $to;
        $this->parentPageView = $pageView;
        $this->redirectPageView = $redirectPageView;
    }

    /**
     * @return string
     */
    public function getFromUrl()
    {
        return $this->fromUrl;
    }

    /**
     * @return string
     */
    public function getToUrl()
    {
        return $this->toUrl;
    }

    /**
     * Read-only access to the PageView who this redirect belongs to.
     *
     * @return BasePageView
     */
    public function getParentPageView()
    {
        return clone $this->parentPageView;
    }

    /**
     * The PageView that was generated to create this redirect as a flat file.
     *
     * @return BasePageView
     */
    public function getRedirectPageView()
    {
        return $this->redirectPageView;
    }
}
