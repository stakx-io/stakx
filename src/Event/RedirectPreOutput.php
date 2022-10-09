<?php declare(strict_types=1);

/**
 * @copyright 2018 Vladimir Jimenez
 * @license   https://github.com/stakx-io/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Event;

use allejo\stakx\Document\BasePageView;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * This event is fired before a redirect is created. This event provides read-only access to the parent PageView and
 * write access to the PageView generated to write a flat redirect file.
 *
 * @since 0.2.1
 */
class RedirectPreOutput extends Event
{
    final public const NAME = 'redirect.preoutput';

    /**
     * @param string       $fromUrl
     * @param string       $toUrl
     * @param BasePageView $parentPageView
     * @param BasePageView $redirectPageView
     */
    public function __construct(private $fromUrl, private $toUrl, private $parentPageView, private $redirectPageView)
    {
    }

    public function getFromUrl(): string
    {
        return $this->fromUrl;
    }

    public function getToUrl(): string
    {
        return $this->toUrl;
    }

    /**
     * Read-only access to the PageView who this redirect belongs to.
     */
    public function getParentPageView(): BasePageView
    {
        return clone $this->parentPageView;
    }

    /**
     * The PageView that was generated to create this redirect as a flat file.
     */
    public function getRedirectPageView(): BasePageView
    {
        return $this->redirectPageView;
    }
}
