<?php

/**
 * @copyright 2018 Vladimir Jimenez
 * @license   https://github.com/stakx-io/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Filesystem;

abstract class FileExplorerMatcher
{
    /**
     * Return a matcher callable for files that have been modified after a certain timestamp.
     *
     * @param \DateTime $time
     *
     * @throws \Exception
     *
     * @return \Closure
     */
    public static function modifiedAfter(\DateTime $time)
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
