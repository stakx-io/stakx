<?php

/**
 * @copyright 2017 Vladimir Jimenez
 * @license   https://github.com/allejo/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\System;

/**
 * An object that will accept a *nix style path and return a cross-platform ready file path.
 */
class FilePath
{
    private $originalPath;
    private $pathNodes;

    public function __construct($filePath)
    {
        $this->originalPath = $filePath;
        $this->pathNodes = explode('/', $filePath);
    }

    public function __toString()
    {
        return implode(DIRECTORY_SEPARATOR, $this->pathNodes);
    }
}
