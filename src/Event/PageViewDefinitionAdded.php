<?php declare(strict_types=1);

/**
 * @copyright 2018 Vladimir Jimenez
 * @license   https://github.com/stakx-io/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Event;

use allejo\stakx\Filesystem\Folder;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * A notification-only event that is triggered whenever a valid PageView folder is scanned.
 *
 * @since 0.2.0
 */
class PageViewDefinitionAdded extends Event
{
    final public const NAME = 'pageview.definition.added';

    public function __construct(private readonly Folder $folder)
    {
    }

    public function getFolder(): string
    {
        return $this->folder;
    }
}
