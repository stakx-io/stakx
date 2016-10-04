<?php

/**
 * @copyright 2016 Vladimir Jimenez
 * @license   https://github.com/allejo/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Manager;
use allejo\stakx\Object\FrontMatterObject;

/**
 * Interface ITrackable
 *
 * @package allejo\stakx\Manager
 */
interface ITrackable
{
    /**
     * Add a FrontMatterObject based object to the tracker
     *
     * @param FrontMatterObject $trackedItem
     */
    public function addToTracker (&$trackedItem, $collection = null);

    /**
     * Check whether a file is tracked
     *
     * @param  string $key The relative path of the file
     *
     * @return bool
     */
    public function isTracked ($key);

    /**
     * Save data to the tracker with a reference to the file it came from
     *
     * @param string      $key        The name of the file
     * @param mixed       $data       The data to save the
     * @param string      $filePath   The relative file path from the root of the website
     * @param string|null $collection The name of the collection this data belongs to, if any
     */
    public function saveToTracker ($key, $data, $filePath, $collection = null);

    /**
     * Remove an entry from the tracked items array
     *
     * @param mixed $trackedItem
     */
    public function delFromTracker ($trackedItem, $collection = null);
}