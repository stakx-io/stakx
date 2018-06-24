<?php

/**
 * @copyright 2018 Vladimir Jimenez
 * @license   https://github.com/stakx-io/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Event;

use Symfony\Component\EventDispatcher\Event;

/**
 * A notification-only event that is triggered whenever a valid PageView folder is scanned.
 *
 * @since 0.2.0
 */
class PageViewDefinitionAdded extends Event
{
    const NAME = 'pageview.definition.added';

    private $folderName;

    public function __construct($folderName)
    {
        $this->folderName = $folderName;
    }

    /**
     * @return string
     */
    public function getFolderName()
    {
        return $this->folderName;
    }
}
