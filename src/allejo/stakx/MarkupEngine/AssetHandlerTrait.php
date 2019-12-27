<?php

namespace allejo\stakx\MarkupEngine;

use allejo\stakx\Document\ContentItem;
use allejo\stakx\Filesystem\File;
use allejo\stakx\Filesystem\FilesystemLoader as fs;
use allejo\stakx\Manager\AssetManager;

/**
 * @since 0.2.1
 */
trait AssetHandlerTrait
{
    /** @var AssetManager */
    protected $assetManager;

    /** @var ContentItem */
    protected $contentItem;

    /**
     * Get a File object from a local path relative to the ContentItem.
     *
     * @param string $localPath
     *
     * @return File
     */
    private function getFileFromPath($localPath)
    {
        $path = fs::path($this->contentItem->getAbsoluteFilePath())
            ->getParentDirectory()
            ->generatePath($localPath);

        return new File($path);
    }

    /**
     * @param File $file
     *
     * @return string
     */
    private function getPermalinkFromFile(File $file)
    {
        $folder = fs::path($this->contentItem->getTargetFile())->getParentDirectory();

        return fs::getRelativePath($folder->generatePath($file->getFilename()));
    }

    /**
     * Check if a given string is a valid URL.
     *
     * @param string $url
     *
     * @return bool
     */
    private function isValidURL($url)
    {
        return filter_var($url, FILTER_VALIDATE_URL);
    }

    /**
     * @param string $path
     *
     * @return void
     */
    protected function registerAsset($path)
    {
        if ($this->isValidURL($path))
        {
            return;
        }

        $asset = $this->getFileFromPath($path);
        $permalink = $this->getPermalinkFromFile($asset);

        $this->assetManager->addAsset($permalink, $asset);
    }
}
