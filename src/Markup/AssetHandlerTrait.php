<?php declare(strict_types=1);

/**
 * @copyright 2018 Vladimir Jimenez
 * @license   https://github.com/stakx-io/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Markup;

use allejo\stakx\Document\ContentItem;
use allejo\stakx\Filesystem\File;
use allejo\stakx\Filesystem\FilesystemLoader as fs;
use allejo\stakx\Manager\AssetManager;

/**
 * This trait provides the functionality for registering linked files with our AssetManager.
 *
 * @since 0.2.1
 */
trait AssetHandlerTrait
{
    protected AssetManager $assetManager;

    protected ?ContentItem $contentItem;

    /**
     * Given a URL to a local path, register this function with the AssetManager so it can be available at compile time.
     *
     * @since 0.2.1
     */
    protected function registerAsset(string $path): void
    {
        if ($this->isValidURL($path)) {
            return;
        }

        $asset = $this->getFileFromPath($path);
        $permalink = $this->getPermalinkFromFile($asset);

        $this->assetManager->addExplicitAsset($permalink, $asset);
    }

    /**
     * Get a File object from a local path relative to the ContentItem.
     */
    private function getFileFromPath(string $localPath): File
    {
        $path = fs::path($this->contentItem->getAbsoluteFilePath())
            ->getParentDirectory()
            ->generatePath($localPath)
        ;

        return new File($path);
    }

    /**
     * Get the permalink this file would belong at.
     *
     * This is taken from the ContentItem's target path and puts the asset at the same location as a sibling.
     */
    private function getPermalinkFromFile(File $file): string
    {
        $folder = fs::path($this->contentItem->getTargetFile())->getParentDirectory();

        return fs::getRelativePath($folder->generatePath($file->getFilename()));
    }

    /**
     * Check if a given string is a valid URL.
     */
    private function isValidURL(string $url): bool
    {
        return filter_var($url, FILTER_VALIDATE_URL);
    }
}
