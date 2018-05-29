<?php

/**
 * @copyright 2018 Vladimir Jimenez
 * @license   https://github.com/allejo/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\AssetEngine;

use __;
use allejo\stakx\Exception\UnsupportedAssetEngineException;

class AssetEngineManager
{
    private $enginesByExtension = [];

    public function addAssetEngines(/* iterable */ $assetEngines)
    {
        foreach ($assetEngines as $assetEngine)
        {
            $this->addAssetEngine($assetEngine);
        }
    }

    public function addAssetEngine(AssetEngine $assetEngine)
    {
        $extensions = $assetEngine->getExtensions();
        $primaryExt = __::first($extensions);

        foreach ($extensions as $k => $extension)
        {
            if ($k === 0)
            {
                $this->enginesByExtension[$extension] = $assetEngine;
            }
            else
            {
                $this->enginesByExtension[$extension] = &$this->enginesByExtension[$primaryExt];
            }
        }
    }

    public function getEngineByExtension($extension)
    {
        if (isset($this->enginesByExtension[$extension]))
        {
            return $this->enginesByExtension[$extension];
        }

        throw new UnsupportedAssetEngineException($extension, 'There is no support to handle this asset type.');
    }
}
