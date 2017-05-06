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
use allejo\stakx\System\FileExplorer;
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
     * Check to see if the file belongs inside of one the folders being tracked by this manager.
     *
     * @param string $filePath
     *
     * @return bool True if the file is inside a tracked folder
     */
    public function isHandled($filePath)
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
     * Check whether a file is tracked.
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
     * @param SplFileInfo|string $filePath
     *
     * @return mixed|null
     */
    public function createNewItem($filePath)
    {
        return $this->handleTrackableItem($filePath);
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

    /**
     * Return an array of JailedDocuments created from the tracked items
     *
     * @return JailedDocument[]
     */
    protected function getJailedTrackedItems()
    {
        $jailItems = array();

        /**
         * @var string       $key
         * @var TwigDocument $item
         */
        foreach ($this->trackedItemsFlattened as &$item)
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

    ///
    // Array Tracking
    ///

    /**
     * Save data to the tracker with a reference to the file it came from.
     *
     * @param string      $key         The name of the file
     * @param mixed       $data        The data to save the
     * @param string      $relFilePath The relative file path from the root of the website
     * @param string|null $namespace   The name of the collection this data belongs to, if any
     */
    protected function addArrayToTracker($key, $data, $relFilePath, $namespace = null)
    {
        if (is_null($namespace))
        {
            $this->trackedItems[$key] = $data;
            $this->trackedItemsFlattened[$relFilePath] = &$this->trackedItems[$key];
        }
        else
        {
            $this->trackedItems[$namespace][$key] = $data;
            $this->trackedItemsFlattened[$relFilePath] = &$this->trackedItems[$namespace][$key];
        }
    }

    /**
     * Remove all data related to an array that was saved.
     *
     * @param string      $key
     * @param string      $filePath
     * @param string|null $namespace
     */
    protected function delArrayFromTracker($key, $filePath, $namespace = null)
    {
        if (is_null($namespace))
        {
            unset($this->trackedItems[$key]);
        }
        else
        {
            unset($this->trackedItems[$namespace][$key]);
        }

        unset($this->trackedItemsFlattened[$filePath]);
    }

    ///
    // Object Tracking
    ///

    /**
     * Add a FrontMatterObject based object to the tracker.
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

    /**
     * Save a folder that is tracked by this manager and its respective options.
     *
     * @param string $folderPath
     * @param array  $options
     */
    protected function saveFolderDefinition($folderPath, $options = array())
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
    protected function saveTrackerOptions($filePath, $options = array())
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

    /**
     * Parse the specified folder for items to track.
     *
     * @param string $path
     * @param mixed  $options  Special options that will be passed to the static::parseTrackableItem() implementation
     * @param array  $includes
     * @param array  $excludes
     */
    protected function scanTrackableItems($path, $options = array(), $includes = array(), $excludes = array())
    {
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
     *  - TrackingManager::addArrayToTracker()
     *  - TrackingManager::saveTrackerOptions()
     *
     * @param SplFileInfo $filePath
     * @param mixed       $options
     *
     * @return mixed|null
     */
    abstract protected function handleTrackableItem($filePath, $options = array());
}
