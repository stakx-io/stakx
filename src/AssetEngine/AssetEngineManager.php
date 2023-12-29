<?php

/**
 * @copyright 2018 Vladimir Jimenez
 * @license   https://github.com/stakx-io/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\AssetEngine;

use allejo\stakx\Exception\UnsupportedAssetEngineException;

class AssetEngineManager
{
    /** @var array<string, AssetEngineInterface> Extensions are stored as keys and engines as corresponding values */
    private array $enginesByExtension = [];

    /** @var array<string, AssetEngineInterface> Folder paths are stored as keys and engines as corresponding values */
    private array $foldersToWatch = [];

    /** @var AssetEngineInterface[] All registered engines */
    private array $engines;

    public function addAssetEngines(iterable $assetEngines): void
    {
        foreach ($assetEngines as $assetEngine)
        {
            $this->addAssetEngine($assetEngine);
        }
    }

    public function addAssetEngine(AssetEngineInterface $assetEngine): void
    {
        $extensions = $assetEngine->getExtensions();

        $e = $this->engines[] = $assetEngine;

        foreach ($extensions as $extension)
        {
            $this->enginesByExtension[$extension] = $e;
        }

        $this->foldersToWatch[$assetEngine->getFolder()] = $e;
    }

    /**
     * @return AssetEngineInterface[]
     */
    public function getEngines(): array
    {
        return $this->engines;
    }

    public function getEngineByExtension($extension)
    {
        if (isset($this->enginesByExtension[$extension]))
        {
            return $this->enginesByExtension[$extension];
        }

        throw new UnsupportedAssetEngineException($extension, "There is no support to handle the '{$extension}' asset type.");
    }

    /**
     * @return AssetEngineInterface[]
     */
    public function getFoldersToWatch(): array
    {
        return $this->foldersToWatch;
    }
}
