<?php

/**
 * @copyright 2016 Vladimir Jimenez
 * @license   https://github.com/allejo/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Manager;

use allejo\stakx\Object\FrontMatterObject;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

/**
 * Class TrackingManager
 *
 * @package allejo\stakx\Manager
 */
abstract class TrackingManager extends BaseManager
{
    /**
     * An array corresponding with $folderDefinitions to store metadata regarding a specificc folder
     *
     * $folderDefinitionsOption['<folder path>'] = array()
     *
     * @var string[]
     */
    protected $folderDefinitionsOptions;

    /**
     * An array of folders which tracked items are stored in
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
     * @var array
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
     * The storage used for either FrontMatterObjects or DataItems in the respective static classes
     *
     * $trackedItems['<namespace>']['<file name w/o extension>'] = mixed
     * $trackedItems['<file name w/o extension>'] = mixed
     *
     * @var array
     */
    protected $trackedItems;

    /**
     * Set to true when file tracking is enabled
     *
     * @var bool
     */
    protected $tracking;

    public function __construct()
    {
        parent::__construct();

        $this->folderDefinitionsOptions = array();
        $this->folderDefinitions = array();
        $this->trackedItemsFlattened = array();
        $this->trackedItemsOptions = array();
        $this->trackedItems = array();
        $this->tracking = false;
    }

    /**
     * Save data to the tracker with a reference to the file it came from
     *
     * @param string      $key       The name of the file
     * @param mixed       $data      The data to save the
     * @param string      $filePath  The relative file path from the root of the website
     * @param string|null $namespace The name of the collection this data belongs to, if any
     */
    public function addArrayToTracker ($key, $data, $filePath, $namespace = null)
    {
        if (is_null($namespace))
        {
            $this->trackedItems[$key] = $data;
            $this->trackedItemsFlattened[$filePath] = &$this->trackedItems[$key];
        }
        else
        {
            $this->trackedItems[$namespace][$key] = $data;
            $this->trackedItemsFlattened[$filePath] = &$this->trackedItems[$namespace][$key];
        }
    }

    /**
     * Add a FrontMatterObject based object to the tracker
     *
     * @param FrontMatterObject $trackedItem
     * @param string            $key
     * @param string|null       $namespace
     */
    public function addObjectToTracker ($trackedItem, $key, $namespace = null)
    {
        if (!($trackedItem instanceof FrontMatterObject))
        {
            throw new \InvalidArgumentException('Only objects can be added to the tracker');
        }

        $this->addArrayToTracker($key, $trackedItem, $trackedItem->getRelativeFilePath(), $namespace);
    }

    /**
     * Remove all data related to an array that was saved
     *
     * @param string      $key
     * @param string      $filePath
     * @param string|null $namespace
     */
    public function delArrayFromTracker($key, $filePath, $namespace = null)
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

    /**
     * Remove an entry from the tracked items array
     *
     * @param mixed       $trackedItem
     * @param string|null $namespace
     */
    public function delObjectFromTracker ($trackedItem, $namespace = null)
    {
        $this->delArrayFromTracker(
            $trackedItem->getFileName(),
            $trackedItem->getRelativeFilePath(),
            $namespace
        );
    }

    /**
     * Whether or not to enable tracking of files.
     *
     * Setting this to false will disable a lot of the overhead and caching done when a project is being watched
     *
     * @param bool $enabled
     */
    public function enableTracking ($enabled)
    {
        $this->tracking = $enabled;
    }

    /**
     * Check to see if the file belongs inside of one the folders being tracked by this manager
     *
     * @param  string $filePath
     *
     * @return bool True if the file is inside a tracked folder
     */
    public function isHandled ($filePath)
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
     * Check whether a file is tracked
     *
     * @param  string $filePath The relative path of the file
     *
     * @return bool
     */
    public function isTracked ($filePath)
    {
        return array_key_exists($filePath, $this->trackedItemsFlattened);
    }

    /**
     * @param SplFileInfo|string $filePath
     */
    public function createNewItem ($filePath)
    {
        $this->handleTrackableItem($filePath);
    }

    /**
     * Update the contents of a specified file
     *
     * @param SplFileInfo|string $filePath The relative path of the file
     *
     * @return mixed|null
     */
    public function refreshItem ($filePath)
    {
        return $this->handleTrackableItem(
            $filePath,
            $this->trackedItemsOptions[$filePath]
        );
    }

    /**
     * Save a folder that is tracked by this manager and its respective options
     *
     * @param string $folderPath
     * @param array  $options
     */
    public function saveFolderDefinition ($folderPath, $options = array())
    {
        $this->folderDefinitions[] = $folderPath;
        $this->folderDefinitionsOptions[$folderPath] = $options;
    }

    /**
     * Save any options related to an item needed in order to refresh the content
     *
     * @param string $filePath
     * @param array $options
     */
    public function saveTrackerOptions ($filePath, $options = array())
    {
        $this->trackedItemsOptions[$filePath] = $options;
    }

    /**
     * Parse the specified folder for items to track
     *
     * @param Finder|string $pathOrFinder
     * @param mixed  $options  Special options that will be passed to the static::parseTrackableItem() implementation
     * @param array  $includes
     * @param array  $excludes
     */
    protected function scanTrackableItems ($pathOrFinder, $options = array(), $includes = array(), $excludes = array())
    {
        if ($pathOrFinder instanceof Finder)
        {
            $finder = $pathOrFinder;
        }
        else
        {
            $finder = $this->fs->getFinder(
                $includes,
                $excludes,
                $this->fs->absolutePath($pathOrFinder)
            );
        }

        /** @var SplFileInfo $file */
        foreach ($finder as $file)
        {
            $this->handleTrackableItem($file, $options);
        }
    }

    /**
     * @param  SplFileInfo $filePath
     * @param  mixed       $options
     *
     * @return mixed|null
     */
    abstract protected function handleTrackableItem ($filePath, $options = array());
}