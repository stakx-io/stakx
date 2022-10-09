<?php declare(strict_types=1);

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
use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class AssetManager extends TrackingManager
{
    /**
     * The location of where to write files to.
     */
    protected WritableFolder $outputDirectory;

    /**
     * Files or patterns to exclude from copying.
     */
    protected array $excludes;

    /**
     * Files or patterns to ensure are copied regardless of excluded patterns.
     */
    protected array $includes;

    /** @var array<string, File> */
    protected array $explicitAssets;

    public function __construct(protected EventDispatcherInterface $eventDispatcher, protected LoggerInterface $logger)
    {
        $this->explicitAssets = [];
    }

    /**
     * @param string $permalink
     */
    public function addExplicitAsset($permalink, File $file): void
    {
        $this->explicitAssets[$permalink] = $file;
    }

    /**
     * @param string $permalink
     */
    public function getExplicitAsset($permalink): ?File
    {
        if (isset($this->explicitAssets[$permalink])) {
            return $this->explicitAssets[$permalink];
        }

        return null;
    }

    public function configureFinder($includes = [], $excludes = []): void
    {
        $this->excludes = $excludes;
        $this->includes = $includes;
    }

    /**
     * Set the target directory of where files should be written to.
     *
     * @param WritableFolder $directory
     */
    public function setFolder($directory): void
    {
        $this->outputDirectory = $directory;
    }

    /**
     * Copy all of the assets.
     */
    public function copyFiles(): void
    {
        $this->logger->notice('Copying manual assets...');

        foreach ($this->explicitAssets as $targetPath => $manualAsset) {
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
    public function refreshItem($filePath): mixed
    {
        return $this->handleTrackableItem($filePath, [
            'prefix' => '',
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function shouldBeTracked($filePath): bool
    {
        return $this->fileExplorer->matchesPattern($filePath);
    }

    /**
     * {@inheritdoc}
     */
    public function createNewItem($filePath): mixed
    {
        return $this->handleTrackableItem($filePath, [
            'prefix' => '',
        ]);
    }

    /**
     * {@inheritdoc}
     */
    protected function handleTrackableItem(File $file, array $options = []): mixed
    {
        if (!$file->exists()) {
            return null;
        }

        $filePath = $file->getRealPath();
        $pathToStrip = fs::appendPath(Service::getWorkingDirectory(), $options['prefix']);

        if (isset($options['siteTargetPath'])) {
            $siteTargetPath = $options['siteTargetPath'];
        } else {
            $siteTargetPath = ltrim(str_replace($pathToStrip, '', $filePath), DIRECTORY_SEPARATOR);
        }

        try {
            $this->addFileToTracker($file);
            $this->saveTrackerOptions($file->getRelativeFilePath(), $options);

            $this->outputDirectory->copyFile($filePath, $siteTargetPath);
            $this->logger->info('Copying file: {file}...', [
                'file' => $file->getRelativeFilePath(),
            ]);
        } catch (Exception $e) {
            $this->logger->error($e->getMessage());
        }

        return null;
    }
}
