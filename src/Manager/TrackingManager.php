<?php

/**
 * @copyright 2016 Vladimir Jimenez
 * @license   https://github.com/allejo/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Manager;

use allejo\stakx\Object\FrontMatterObject;

/**
 * Class TrackingManager
 *
 * @package allejo\stakx\Manager
 */
abstract class TrackingManager extends BaseManager implements Trackable
{
    protected $trackedItemsFlattened;

    /**
     * $trackedItems['<collection name>']['<file name w/o extension>'] = typeof(FrontMatterObject)
     * $trackedItems['<file name w/o extension>'] = typeof(FrontMatterObject)
     *
     * @var array
     */
    protected $trackedItems;

    public function __construct()
    {
        parent::__construct();

        $this->trackedItemsFlattened = array();
        $this->trackedItems = array();
    }

    /**
     * {@inheritdoc}
     */
    public function addToTracker (&$trackedItem, $collection = null)
    {
        if (!($trackedItem instanceof FrontMatterObject))
        {
            throw new \InvalidArgumentException('Only objects can be added to the tracker');
        }

        if (is_null($collection))
        {
            $this->trackedItems[$trackedItem->getFileName()] = &$trackedItem;
        }
        else
        {
            $this->trackedItems[$collection][$trackedItem->getFileName()] = &$trackedItem;
        }

        $this->trackedItemsFlattened[$trackedItem->getRelativeFilePath()] = &$trackedItem;
    }

    /**
     * {@inheritdoc}
     */
    public function isTracked ($key)
    {
        return array_key_exists($key, $this->trackedItemsFlattened);
    }

    /**
     * {@inheritdoc}
     */
    public function saveToTracker($key, $data, $filePath, $collection = null)
    {
        if (is_null($collection))
        {
            $this->trackedItems[$key] = $data;
        }
        else
        {
            $this->trackedItems[$collection][$key] = $data;
        }


        $this->trackedItemsFlattened[$filePath] = $data;
    }

    /**
     * {@inheritdoc}
     */
    public function delFromTracker ($trackedItem, $collection = null)
    {
        if (is_null($collection))
        {
            unset($this->trackedItems[$trackedItem->getFileName()]);
        }
        else
        {
            unset($this->trackedItems[$collection][$trackedItem->getFileName()]);
        }

        unset($this->trackedItemsFlattened[$trackedItem->getRelativeFilePath()]);
    }

    /**
     * Parse the specified folder for items to track
     *
     * @param string $folder
     */
    abstract protected function parseTrackableItems ($folder);
}