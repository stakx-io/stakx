<?php declare(strict_types=1);

/**
 * @copyright 2018 Vladimir Jimenez
 * @license   https://github.com/stakx-io/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\AssetEngine;

use allejo\stakx\Exception\UnsupportedAssetEngineException;

class AssetEngineManager
{
    /** @var array<string, AssetEngineInterface> */
    private array $enginesByExtension = [];

    /** @var array<string, AssetEngineInterface> */
    private array $foldersToWatch = [];

    /** @var AssetEngineInterface[] */
    private array $engines = [];

    /**
     * @param iterable<AssetEngineInterface> $assetEngines
     */
    public function addAssetEngines(iterable $assetEngines): void
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

    /**
     * @return AssetEngineInterface[]
     */
    public function getEngines(): array
    {
        return $this->engines;
    }

    /**
     * @throws UnsupportedAssetEngineException when no engine available to handle the given extension
     */
    public function getEngineByExtension(string $extension): AssetEngineInterface
    {
        if (isset($this->enginesByExtension[$extension])) {
            return $this->enginesByExtension[$extension];
        }

        throw new UnsupportedAssetEngineException($extension, "There is no support to handle the '{$extension}' asset type.");
    }

    /**
     * @return array<string, AssetEngineInterface>
     */
    public function getFoldersToWatch(): array
    {
        return $this->foldersToWatch;
    }
}
