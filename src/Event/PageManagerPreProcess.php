<?php declare(strict_types=1);

/**
 * @copyright 2018 Vladimir Jimenez
 * @license   https://github.com/stakx-io/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Event;

use allejo\stakx\Manager\PageManager;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * This event is fired before the PageManager begins processing PageViews.
 *
 * @since 0.2.0
 */
class PageManagerPreProcess extends Event
{
    public const NAME = 'pagemanager.process.pre_process';

    public function __construct(private readonly PageManager $manager)
    {
    }

    public function getPageManager(): PageManager
    {
        return $this->manager;
    }
}
