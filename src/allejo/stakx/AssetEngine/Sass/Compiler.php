<?php

/**
 * @copyright 2018 Vladimir Jimenez
 * @license   https://github.com/stakx-io/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\AssetEngine\Sass;

use allejo\stakx\RuntimeStatus;
use allejo\stakx\Service;
use Leafo\ScssPhp\Compiler as BaseCompiler;

/**
 * A modified version of our Sass compiler.
 */
class Compiler extends BaseCompiler
{
    /**
     * @var int
     */
    protected $lastTouched;

    /**
     * Overridden method for handling Sass import statements.
     *
     * Our compiler will need to be able to refresh the Sass output if an import
     * has changed since it was cached. This is used solely for the purpose of
     * supporting our built-in dev server so that it can recompile Sass with the
     * latest changes made in imports.
     *
     * @param string $path
     * @param array  $out
     */
    protected function importFile($path, $out)
    {
        $serveMode = Service::hasRunTimeFlag(RuntimeStatus::IN_SERVE_MODE);
        $cacheMode = Service::hasRunTimeFlag(RuntimeStatus::USING_CACHE);

        if (!($serveMode || $cacheMode))
        {
            parent::importFile($path, $out);

            return;
        }

        $realPath = realpath($path);

        // If we've compiled our Sass once already, then the last modified time
        // will exist in our listing. At this point, we already have Sass trees
        // cached, so we should only clear the outdated ones.
        if (isset($this->lastTouched[$realPath]))
        {
            // If the imported file has been modified since our last
            // compilation, then we will need to clear the cached tree to allow
            // our engine to build a new tree.
            if (($t = filemtime($realPath)) > $this->lastTouched[$realPath])
            {
                $this->clearImportCache($realPath);
                $this->lastTouched[$realPath] = $t;
            }
        }
        else
        {
            $this->lastTouched[$realPath] = filemtime($realPath);
        }

        parent::importFile($path, $out);
    }

    /**
     * Remove an import from the Sass cache.
     *
     * @param string|null $file The absolute path to an imported file that will
     *                          be cleared from the cache. When given null, the
     *                          entire cache will be cleared.
     */
    public function clearImportCache($file = null)
    {
        if ($file === null)
        {
            $this->importCache = [];

            return;
        }

        unset($this->importCache[$file]);
    }
}
