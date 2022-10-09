<?php declare(strict_types=1);

/**
 * @copyright 2018 Vladimir Jimenez
 * @license   https://github.com/stakx-io/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\AssetEngine;

use allejo\stakx\Exception\UnsupportedAssetEngineException;

class AssetEngineManager
{
    private array $enginesByExtension = [];

    private array $foldersToWatch = [];

    private $engines;

    public function addAssetEngines(/* iterable */ $assetEngines): void
    {
        foreach ($assetEngines as $assetEngine) {
            $this->addAssetEngine($assetEngine);
        }
    }

    public function addAssetEngine(AssetEngineInterface $assetEngine): void
    {
        $extensions = $assetEngine->getExtensions();

        $e = $this->engines[] = $assetEngine;

        foreach ($extensions as $extension) {
            $this->enginesByExtension[$extension] = $e;
        }

        $this->foldersToWatch[$assetEngine->getFolder()] = $e;
    }

    public function getEngines()
    {
        return $this->engines;
    }

    public function getEngineByExtension($extension)
    {
        if (isset($this->enginesByExtension[$extension])) {
            return $this->enginesByExtension[$extension];
        }

        throw new UnsupportedAssetEngineException($extension, "There is no support to handle the '{$extension}' asset type.");
    }

    public function getFoldersToWatch()
    {
        return $this->foldersToWatch;
    }
}
