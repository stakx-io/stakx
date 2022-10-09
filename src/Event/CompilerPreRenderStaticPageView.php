<?php declare(strict_types=1);

/**
 * @copyright 2018 Vladimir Jimenez
 * @license   https://github.com/stakx-io/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Event;

use allejo\stakx\Document\StaticPageView;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * This event is fired before a PageView is rendered and allows you inject custom variables into the templates.
 *
 * @since 0.2.0
 */
class CompilerPreRenderStaticPageView extends Event
{
    use CompilerPreRenderTrait;

    final public const NAME = 'compiler.prerender.static_pageview';

    public function __construct(private readonly StaticPageView $pageView)
    {
    }

    public function getPageView()
    {
        return clone $this->pageView;
    }
}
