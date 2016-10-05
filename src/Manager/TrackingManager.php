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
    protected $trackedItemsFlattened;

    protected $trackedItemsOptions;

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
    public function isTracked ($filePath)
    {
        return array_key_exists($filePath, $this->trackedItemsFlattened);
    }

    /**
     * {@inheritdoc}
     */
    public function refreshItem ($filePath)
    {
        $this->parseTrackableItem(
            $filePath,
            $this->trackedItemsOptions[$filePath]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function saveToTracker ($key, $data, $filePath, $namespace = null)
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
     * {@inheritdoc}
     */
    public function delFromTracker ($trackedItem, $namespace = null)
    {
        if (is_null($namespace))
        {
            unset($this->trackedItems[$trackedItem->getFileName()]);
        }
        else
        {
            unset($this->trackedItems[$namespace][$trackedItem->getFileName()]);
        }

        unset($this->trackedItemsFlattened[$trackedItem->getRelativeFilePath()]);
    }

    /**
     * Parse the specified folder for items to track
     *
     * @param string $folder
     * @param mixed  $options Special options that will be passed to the static::parseTrackableItem() implementation
     */
    protected function parseTrackableItems ($folder, $options = array())
    {
        $finder = new Finder();
        $finder->files()
               ->ignoreDotFiles(true)
               ->ignoreUnreadableDirs()
               ->in($this->fs->absolutePath($folder));

        foreach ($finder as $dataItem)
        {
            $this->parseTrackableItem($dataItem, $options);
        }
    }

    /**
     * @param  SplFileInfo $filePath
     * @param  mixed       $options
     *
     * @return mixed
     */
    abstract protected function parseTrackableItem ($filePath, $options = array());
}