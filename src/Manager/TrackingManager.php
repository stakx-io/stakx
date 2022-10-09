<?php declare(strict_types=1);

/**
 * @copyright 2018 Vladimir Jimenez
 * @license   https://github.com/stakx-io/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Manager;

use allejo\stakx\Document\CollectableItem;
use allejo\stakx\Document\JailedDocument;
use allejo\stakx\Document\ReadableDocument;
use allejo\stakx\Document\TemplateReadyDocument;
use allejo\stakx\Filesystem\File;
use allejo\stakx\Filesystem\FileExplorer;
use allejo\stakx\Filesystem\FileExplorerDefinition;
use allejo\stakx\Filesystem\FilesystemPath;
use allejo\stakx\RuntimeStatus;
use allejo\stakx\Service;
use Closure;

/**
 * Class TrackingManager.
 */
abstract class TrackingManager extends BaseManager
{
    protected ?FileExplorer $fileExplorer = null;

    /**
     * An array corresponding with $folderDefinitions to store metadata regarding a specificc folder.
     *
     * $folderDefinitionsOption['<folder path>'] = array()
     *
     * @var string[]
     */
    protected array $folderDefinitionsOptions = [];

    /**
     * An array of folders which tracked items are stored in.
     *
     * $folderDefinitions[] = '<folder path>'
     *
     * @deprecated This has been superseded by `$watchedFolders`
     *
     * @var string[]
     */
    protected array $folderDefinitions = [];

    /**
     * An array of folder definitions which tracked items are stored in.
     *
     * @var FileExplorerDefinition[]
     */
    protected array $watchedFolders = [];

    /**
     * The storage which contains the same information as $trackedItems but organized by relative file path instead of a
     * namespace or file name without extension.
     *
     * $trackedItemsOptions['<relative file path>'] = mixed
     */
    protected array $trackedItemsFlattened = [];

    /**
     * The storage used to cache any information needed for a specific FrontMatterObject or DataItem.
     *
     * For example, with a DataItem, which is just an array, the file path to the original file can be stored in this
     * array to be accessible in the future to refresh the contents without parsing all of the files again.
     *
     * $trackedItemsOptions['<relative file path>'] = array
     */
    protected array $trackedItemsOptions = [];

    /**
     * The storage used for ReadableDocument in the respective static classes.
     *
     * $trackedItems['<namespace>']['<file name w/o extension>'] = mixed
     * $trackedItems['<file name w/o extension>'] = mixed
     */
    protected array $trackedItems = [];

    /**
     * @param File|string $filePath
     *
     * @return null|mixed
     */
    public function createNewItem($filePath): mixed
    {
        return $this->handleTrackableItem($filePath);
    }

    /**
     * @param FilesystemPath|string $filePath
     *
     * @return null|mixed
     */
    public function getTracked($filePath): mixed
    {
        if ($this->isTracked($filePath)) {
            return $this->trackedItemsFlattened[(string)$filePath];
        }

        return null;
    }

    /**
     * Check whether a file is already being tracked.
     *
     * @param string $filePath The relative path of the file
     */
    public function isTracked($filePath): bool
    {
        return array_key_exists((string)$filePath, $this->trackedItemsFlattened);
    }

    /**
     * Check to see if a given file path matches this tracker's definition and would be tracked.
     *
     * This function should be used to check whether or not to add a file to this tracker after the initial scan has
     * already happened.
     *
     * @param string $filePath
     *
     * @return bool True if the file is inside a tracked folder
     */
    public function shouldBeTracked($filePath): bool
    {
        foreach ($this->folderDefinitions as $folder) {
            if (str_starts_with($filePath, $folder)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Update the contents of a specified file.
     *
     * @param File|string $filePath The relative path of the file
     *
     * @return null|mixed
     */
    public function refreshItem($filePath): mixed
    {
        return $this->handleTrackableItem($filePath);
    }

    //
    // Internal object handling
    //

    /**
     * Initialize a namespace that will be tracked.
     *
     * @param string $namespace
     */
    protected function declareTrackingNamespace($namespace): void
    {
        if (!isset($this->trackedItems[$namespace])) {
            $this->trackedItems[$namespace] = [];
        }
    }

    protected function addFileToTracker(File &$file): void
    {
        $this->trackedItemsFlattened[$file->getRelativeFilePath()] = &$file;
    }

    protected function delFileFromTracker(File &$file): void
    {
        unset($this->trackedItemsFlattened[$file->getRelativeFilePath()]);
    }

    /**
     * Add a ReadableDocument to the tracker.
     *
     * @param null|string $namespace
     */
    protected function addObjectToTracker(ReadableDocument &$trackedItem, $namespace = null): void
    {
        if ($namespace == null) {
            $this->trackedItems[$trackedItem->getIndexName()] = &$trackedItem;
        } else {
            $this->trackedItems[$namespace][$trackedItem->getIndexName()] = &$trackedItem;
        }

        $this->trackedItemsFlattened[$trackedItem->getIndexName()] = &$trackedItem;
    }

    /**
     * Remove an entry from the tracked items array.
     *
     * @param null|string $namespace
     */
    protected function delObjectFromTracker(ReadableDocument &$trackedItem, $namespace = null): void
    {
        if ($namespace == null) {
            unset($this->trackedItems[$trackedItem->getIndexName()]);
        } else {
            unset($this->trackedItems[$namespace][$trackedItem->getIndexName()]);
        }

        unset($this->trackedItemsFlattened[$trackedItem->getIndexName()]);
    }

    //
    // Extra options stored for future use
    //

    /**
     * Save a folder that is tracked by this manager and its respective options.
     *
     * @deprecated
     *
     * @param string $folderPath
     */
    protected function saveFolderDefinition($folderPath, array $options = []): void
    {
        $this->folderDefinitions[] = $folderPath;
        $this->folderDefinitionsOptions[(string)$folderPath] = $options;
    }

    /**
     * Save any options related to an item needed in order to refresh the content.
     *
     * @param string $filePath
     */
    protected function saveTrackerOptions($filePath, array $options = []): void
    {
        $this->trackedItemsOptions[$filePath] = $options;
    }

    /**
     * Delete any options that were saved corresponding to an item.
     *
     * @param string $filePath
     */
    protected function forgetTrackerOptions($filePath): void
    {
        unset($this->trackedItemsOptions[$filePath]);
    }

    //
    // Handling of trackable items
    //

    /**
     * Parse the specified folder for items to track.
     *
     * @param array $options Special options that will be passed to the `static::parseTrackableItem()`
     *                       implementation
     */
    protected function scanTrackableItems(FileExplorerDefinition $def, array $options = []): void
    {
        $this->watchedFolders[$def->folder->getAbsolutePath()] = $def;

        if (empty($def->excludes)) {
            $def->excludes = self::$documentIgnoreList;
        }

        $fileExplorer = FileExplorer::createFromDefinition($def);
        $fileIterator = $fileExplorer->getFileIterator();

        foreach ($fileIterator as $file) {
            $this->handleTrackableItem($file, $options);
        }
    }

    /**
     * Handle a specific file type, parse it into the appropriate object type, and add it to the tracker.
     *
     * This function should make use of the appropriate functions:
     *
     *  - TrackingManager::addObjectToTracker()
     *  - TrackingManager::addFileToTracker()
     *  - TrackingManager::saveTrackerOptions()
     *
     * @return null|mixed
     */
    abstract protected function handleTrackableItem(File $filePath, array $options = []): mixed;

    //
    // Utility functions
    //

    /**
     * Return an array of JailedDocuments created from the tracked items.
     *
     * @param JailedDocument[] $elements An array of elements to get jailed versions of
     * @param Closure          $name     a closure to generate the name of the element that will be used as the key in
     *                                   this associative array
     *
     * @return JailedDocument[]|JailedDocument[][]
     */
    protected static function getJailedTrackedItems(array &$elements, Closure $name = null): array
    {
        $jailItems = [];

        /** @var CollectableItem|ReadableDocument $item */
        foreach ($elements as &$item) {
            if ($item instanceof TemplateReadyDocument) {
                if (!Service::hasRunTimeFlag(RuntimeStatus::USING_DRAFTS) && $item->isDraft()) {
                    continue;
                }
            }

            $keyName = ($name === null) ? $item->getRelativeFilePath() : $name($item);

            if (empty($item->getNamespace())) {
                $jailItems[$keyName] = $item->createJail();
            } else {
                $jailItems[$item->getNamespace()][$keyName] = $item->createJail();
            }
        }

        return $jailItems;
    }
}
