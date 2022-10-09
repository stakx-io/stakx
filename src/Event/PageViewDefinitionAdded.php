<?php

/**
 * @copyright 2018 Vladimir Jimenez
 * @license   https://github.com/stakx-io/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Event;

use allejo\stakx\Filesystem\Folder;
use Symfony\Component\EventDispatcher\Event;

/**
 * A notification-only event that is triggered whenever a valid PageView folder is scanned.
 *
 * @since 0.2.0
 */
class PageViewDefinitionAdded extends Event
{
    const NAME = 'pageview.definition.added';

    private $folder;

    public function __construct(Folder $folder)
    {
        $this->folder = $folder;
    }

    /**
     * @return string
     */
    public function getFolder()
    {
        return $this->folder;
    }
}
