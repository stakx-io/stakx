<?php

/**
 * @copyright 2018 Vladimir Jimenez
 * @license   https://github.com/stakx-io/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Event;

use allejo\stakx\Manager\PageManager;
use Symfony\Component\EventDispatcher\Event;

/**
 * This event is fired before the PageManager begins processing PageViews.
 *
 * @since 0.2.0
 */
class PageManagerPreProcess extends Event
{
    const NAME = 'pagemanager.process.pre_process';

    private $manager;

    public function __construct(PageManager $manager)
    {
        $this->manager = $manager;
    }

    /**
     * @return PageManager
     */
    public function getPageManager()
    {
        return $this->manager;
    }
}
