<?php

/**
 * @copyright 2018 Vladimir Jimenez
 * @license   https://github.com/stakx-io/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Filesystem;

abstract class FileExplorerMatchers
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
        return function (File $file) use ($time) {
            return $file->getLastModified() > $time->getTimestamp();
        };
    }
}
