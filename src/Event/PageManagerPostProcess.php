<?php

/**
 * @copyright 2018 Vladimir Jimenez
 * @license   https://github.com/stakx-io/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Event;

/**
 * This event is fired whenever the PageManager has finished parsing all of the PageViews.
 *
 * @since 0.2.0
 */
class PageManagerPostProcess extends PageManagerPreProcess
{
    public const NAME = 'pagemanager.process.post_process';
}
