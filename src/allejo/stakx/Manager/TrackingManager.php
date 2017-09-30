<?php

/**
 * @copyright 2017 Vladimir Jimenez
 * @license   https://github.com/allejo/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Manager;

use allejo\stakx\Command\BuildableCommand;
use allejo\stakx\Document\JailedDocument;
use allejo\stakx\Document\PageView;
use allejo\stakx\Document\TrackableDocument;
use allejo\stakx\Document\TwigDocument;
use allejo\stakx\Service;
use allejo\stakx\Filesystem\FileExplorer;
use Symfony\Component\Finder\SplFileInfo;

/**
 * Class TrackingManager.
 */
abstract class TrackingManager extends BaseManager
{
    /**
     * @var FileExplorer
     */
    protected $fileExplorer;

    /**
     * An array corresponding with $folderDefinitions to store metadata regarding a specificc folder.
     *
     * $folderDefinitionsOption['<folder path>'] = array()
     *
     * @var string[]
     */
    protected $folderDefinitionsOptions;

    /**
     * An array of folders which tracked items are stored in.
     *
     * $folderDefinitions[] = '<folder path>'
     *
     * @var string[]
     */
    protected $folderDefinitions;

    /**
     * The storage which contains the same information as $trackedItems but organized by relative file path instead of a
     * namespace or file name without extension.
     *
     * $trackedItemsOptions['<relative file path>'] = mixed
     *
     * @var TrackableDocument[]
     */
    protected $trackedItemsFlattened;

    /**
     * The storage used to cache any information needed for a specific FrontMatterObject or DataItem.
     *
     * For example, with a DataItem, which is just an array, the file path to the original file can be stored in this
     * array to be accessible in the future to refresh the contents without parsing all of the files again.
     *
     * $trackedItemsOptions['<relative file path>'] = array
     *
     * @var array
     */
    protected $trackedItemsOptions;

    /**
     * The storage used for TrackableDocuments in the respective static classes.
     *
     * $trackedItems['<namespace>']['<file name w/o extension>'] = mixed
     * $trackedItems['<file name w/o extension>'] = mixed
     *
     * @var TrackableDocument[]
     */
    protected $trackedItems;

    public function __construct()
    {
        parent::__construct();

        $this->folderDefinitionsOptions = array();
        $this->folderDefinitions = array();
        $this->trackedItemsFlattened = array();
        $this->trackedItemsOptions = array();
        $this->trackedItems = array();
    }

    /**
     * @param SplFileInfo|string $filePath
     *
     * @return mixed|null
     */
    public function createNewItem($filePath)
    {
        return $this->handleTrackableItem($filePath);
    }

    /**
     * Check whether a file is already being tracked.
     *
     * @param string $filePath The relative path of the file
     *
     * @return bool
     */
    public function isTracked($filePath)
    {
        return array_key_exists($filePath, $this->trackedItemsFlattened);
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
    public function shouldBeTracked($filePath)
    {
        foreach ($this->folderDefinitions as $folder)
        {
            if (substr($filePath, 0, strlen($folder)) === $folder)
            {
                return true;
            }
        }

        return false;
    }

    /**
     * Update the contents of a specified file.
     *
     * @param SplFileInfo|string $filePath The relative path of the file
     *
     * @return PageView
     */
    public function refreshItem($filePath)
    {
        return $this->handleTrackableItem($filePath);
    }

    ///
    // Internal object handling
    ///

    protected function addFileToTracker(SplFileInfo &$file)
    {
        $this->trackedItemsFlattened[$file->getRelativePathname()] = &$file;
    }

    protected function delFileFromTracker(SplFileInfo &$file)
    {
        unset($this->trackedItemsFlattened[$file->getRelativePathname()]);
    }

    /**
     * Add a TrackableDocument to the tracker.
     *
     * @param TrackableDocument $trackedItem
     * @param string|null       $namespace
     */
    protected function addObjectToTracker(TrackableDocument &$trackedItem, $namespace = null)
    {
        if ($namespace == null)
        {
            $this->trackedItems[$trackedItem->getObjectName()] = &$trackedItem;
        }
        else
        {
            $this->trackedItems[$namespace][$trackedItem->getObjectName()] = &$trackedItem;
        }

        $this->trackedItemsFlattened[$trackedItem->getRelativeFilePath()] = &$trackedItem;
    }

    /**
     * Remove an entry from the tracked items array.
     *
     * @param TrackableDocument $trackedItem
     * @param string|null       $namespace
     */
    protected function delObjectFromTracker(TrackableDocument &$trackedItem, $namespace = null)
    {
        if ($namespace == null)
        {
            unset($this->trackedItems[$trackedItem->getObjectName()]);
        }
        else
        {
            unset($this->trackedItems[$namespace][$trackedItem->getObjectName()]);
        }

        unset($this->trackedItemsFlattened[$trackedItem->getRelativeFilePath()]);
    }

    ///
    // Extra options stored for future use
    ///

    /**
     * Save a folder that is tracked by this manager and its respective options.
     *
     * @param string $folderPath
     * @param array  $options
     */
    protected function saveFolderDefinition($folderPath, array $options = array())
    {
        $this->folderDefinitions[] = $folderPath;
        $this->folderDefinitionsOptions[$folderPath] = $options;
    }

    /**
     * Save any options related to an item needed in order to refresh the content.
     *
     * @param string $filePath
     * @param array  $options
     */
    protected function saveTrackerOptions($filePath, array $options = array())
    {
        $this->trackedItemsOptions[$filePath] = $options;
    }

    /**
     * Delete any options that were saved corresponding to an item.
     *
     * @param string $filePath
     */
    protected function forgetTrackerOptions($filePath)
    {
        unset($this->trackedItemsOptions[$filePath]);
    }

    ///
    // Handling of trackable items
    ///

    /**
     * Parse the specified folder for items to track.
     *
     * @param string $path
     * @param array  $options  Special options that will be passed to the static::parseTrackableItem() implementation
     * @param array  $includes
     * @param array  $excludes
     */
    protected function scanTrackableItems($path, array $options = array(), array $includes = array(), array $excludes = array())
    {
        $this->folderDefinitions[] = $this->fs->getRelativePath($path);

        $excludes = empty($excludes) ? self::$documentIgnoreList : $excludes;

        $fileExplorerFlags = array_key_exists('fileExplorer', $options) ? $options['fileExplorer'] : null;
        $this->fileExplorer = FileExplorer::create($path, $excludes, $includes, $fileExplorerFlags);
        $fileExplorer = $this->fileExplorer->getExplorer();

        foreach ($fileExplorer as $file)
        {
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
     * @param SplFileInfo $filePath
     * @param mixed       $options
     *
     * @return mixed|null
     */
    abstract protected function handleTrackableItem($filePath, array $options = array());

    ///
    // Utility functions
    ///

    /**
     * Return an array of JailedDocuments created from the tracked items
     *
     * @param array $elements
     *
     * @return JailedDocument[]|JailedDocument[][]
     */
    protected static function getJailedTrackedItems(array &$elements)
    {
        $jailItems = array();

        /**
         * @var string       $key
         * @var TwigDocument $item
         */
        foreach ($elements as &$item)
        {
            if (!Service::getParameter(BuildableCommand::USE_DRAFTS) && $item->isDraft()) { continue; }

            if (empty($item->getNamespace()))
            {
                $jailItems[$item->getObjectName()] = $item->createJail();
            }
            else
            {
                $jailItems[$item->getNamespace()][$item->getObjectName()] = $item->createJail();
            }
        }

        return $jailItems;
    }
}
