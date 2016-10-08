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
abstract class TrackingManager extends BaseManager implements Trackable
{
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

        $this->trackedItemsFlattened = array();
        $this->trackedItemsOptions = array();
        $this->trackedItems = array();
        $this->tracking = false;
    }

    /**
     * {@inheritdoc}
     */
    public function addArrayToTracker ($key, $data, $filePath, $namespace = null)
    {
        if (is_null($namespace))
        {
            $this->trackedItems[$key] = $data;
        }
        else
        {
            $this->trackedItems[$namespace][$key] = $data;
        }

        $this->trackedItemsFlattened[$filePath] = $data;
    }

    /**
     * {@inheritdoc}
     */
    public function addObjectToTracker (&$trackedItem, $namespace = null)
    {
        if (!($trackedItem instanceof FrontMatterObject))
        {
            throw new \InvalidArgumentException('Only objects can be added to the tracker');
        }

        if (is_null($namespace))
        {
            $this->trackedItems[$trackedItem->getName()] = &$trackedItem;
        }
        else
        {
            $this->trackedItems[$namespace][$trackedItem->getName()] = &$trackedItem;
        }

        $this->trackedItemsFlattened[$trackedItem->getRelativeFilePath()] = &$trackedItem;
    }

    /**
     * {@inheritdoc}
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
     * {@inheritdoc}
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
     * {@inheritdoc}
     */
    public function enableTracking ($enabled)
    {
        $this->tracking = $enabled;
    }

    /**
     * {@inheritdoc}
     */
    public function isTracked ($filePath)
    {
        return array_key_exists($filePath, $this->trackedItemsFlattened);
    }

    /**
     * {@inheritdoc}
     */
    public function refreshItem ($filePath)
    {
        $this->handleTrackableItem(
            $filePath,
            $this->trackedItemsOptions[$filePath]
        );
    }

    /**
     * Save any options related to an item needed in order to refresh the content
     *
     * @param string $filePath
     * @param array $options
     */
    public function saveOptions ($filePath, $options = array())
    {
        $this->trackedItemsOptions[$filePath] = $options;
    }

    /**
     * Parse the specified folder for items to track
     *
     * @param string $folder
     * @param mixed  $options  Special options that will be passed to the static::parseTrackableItem() implementation
     * @param array  $includes
     * @param array  $excludes
     */
    protected function scanTrackableItems ($folder, $options = array(), $includes = array(), $excludes = array())
    {
        $finder = $this->fs->getFinder(
            $includes,
            $excludes,
            $this->fs->absolutePath($folder)
        );

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
     * @return mixed
     */
    abstract protected function handleTrackableItem ($filePath, $options = array());
}