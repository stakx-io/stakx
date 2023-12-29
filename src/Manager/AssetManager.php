<?php

/**
 * @copyright 2018 Vladimir Jimenez
 * @license   https://github.com/stakx-io/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Manager;

use allejo\stakx\Filesystem\File;
use allejo\stakx\Filesystem\FileExplorerDefinition;
use allejo\stakx\Filesystem\FilesystemLoader as fs;
use allejo\stakx\Filesystem\Folder;
use allejo\stakx\Filesystem\WritableFolder;
use allejo\stakx\Service;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class AssetManager extends TrackingManager
{
    /**
     * The location of where to write files to.
     *
     * @var WritableFolder
     */
    protected $outputDirectory;

    /**
     * Files or patterns to exclude from copying.
     *
     * @var array
     */
    protected $excludes;

    /**
     * Files or patterns to ensure are copied regardless of excluded patterns.
     *
     * @var array
     */
    protected $includes;

    /** @var array<string, File> */
    protected $explicitAssets;

    protected $eventDispatcher;
    protected $logger;

    public function __construct(EventDispatcherInterface $eventDispatcher, LoggerInterface $logger)
    {
        $this->explicitAssets = [];
        $this->eventDispatcher = $eventDispatcher;
        $this->logger = $logger;
    }

    /**
     * @param string $permalink
     * @param File   $file
     */
    public function addExplicitAsset($permalink, File $file)
    {
        $this->explicitAssets[$permalink] = $file;
    }

    /**
     * @param string $permalink
     *
     * @return File|null
     */
    public function getExplicitAsset($permalink)
    {
        if (isset($this->explicitAssets[$permalink]))
        {
            return $this->explicitAssets[$permalink];
        }

        return null;
    }

    public function configureFinder($includes = [], $excludes = [])
    {
        $this->excludes = $excludes;
        $this->includes = $includes;
    }

    /**
     * Set the target directory of where files should be written to.
     *
     * @param WritableFolder $directory
     */
    public function setFolder($directory)
    {
        $this->outputDirectory = $directory;
    }

    /**
     * Copy all of the assets.
     */
    public function copyFiles()
    {
        $this->logger->notice('Copying manual assets...');

        foreach ($this->explicitAssets as $targetPath => $manualAsset)
        {
            $this->handleTrackableItem($manualAsset, [
                'prefix' => '',
                'siteTargetPath' => $targetPath,
            ]);
        }

        $this->logger->notice('Copying asset files...');

        $folder = new Folder(Service::getWorkingDirectory());
        $def = new FileExplorerDefinition($folder);
        $def->includes = $this->includes;
        $def->excludes = array_merge(
            ['_themes'],
            $this->excludes
        );

        $this->scanTrackableItems($def, [
            'prefix' => '',
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function refreshItem($filePath)
    {
        return $this->handleTrackableItem($filePath, [
            'prefix' => '',
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function shouldBeTracked($filePath)
    {
        return $this->fileExplorer->matchesPattern($filePath);
    }

    /**
     * {@inheritdoc}
     */
    public function createNewItem($filePath)
    {
        return $this->handleTrackableItem($filePath, [
            'prefix' => '',
        ]);
    }

    /**
     * {@inheritdoc}
     */
    protected function handleTrackableItem(File $file, array $options = [])
    {
        if (!$file->exists())
        {
            return;
        }

        $filePath = $file->getRealPath();
        $pathToStrip = fs::appendPath(Service::getWorkingDirectory(), $options['prefix']);

        if (isset($options['siteTargetPath']))
        {
            $siteTargetPath = $options['siteTargetPath'];
        }
        else
        {
            $siteTargetPath = ltrim(str_replace($pathToStrip, '', $filePath), DIRECTORY_SEPARATOR);
        }

        try
        {
            $this->addFileToTracker($file);
            $this->saveTrackerOptions($file->getRelativeFilePath(), $options);

            $this->outputDirectory->copyFile($filePath, $siteTargetPath);
            $this->logger->info('Copying file: {file}...', [
                'file' => $file->getRelativeFilePath(),
            ]);
        }
        catch (\Exception $e)
        {
            $this->logger->error($e->getMessage());
        }
    }
}
