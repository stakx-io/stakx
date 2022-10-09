<?php declare(strict_types=1);

/**
 * @copyright 2018 Vladimir Jimenez
 * @license   https://github.com/stakx-io/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Filesystem;

use Closure;
use DateTime;
use Exception;

abstract class FileExplorerMatcher
{
    /**
     * Return a matcher callable for files that have been modified after a certain timestamp.
     *
     * @throws Exception
     */
    public static function modifiedAfter(DateTime $time): Closure
    {
        return function ($file) use ($time) {
            /** @var File|Folder $file */
            if ($file instanceof Folder) {
                return true;
            }

            return $file->getLastModified() > $time->getTimestamp();
        };
    }
}
