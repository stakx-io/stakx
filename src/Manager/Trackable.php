<?php

/**
 * @copyright 2016 Vladimir Jimenez
 * @license   https://github.com/allejo/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Manager;

use allejo\stakx\Object\FrontMatterObject;
use Symfony\Component\Finder\SplFileInfo;

/**
 * Interface Trackable
 *
 * @package allejo\stakx\Manager
 */
interface Trackable
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
     * @param  string $filePath The relative path of the file
     *
     * @return bool
     */
    public function isTracked ($filePath);

    /**
     * Update the contents of a specified file
     *
     * @param SplFileInfo|string $filePath The relative path of the file
     */
    public function refreshItem ($filePath);

    /**
     * Save data to the tracker with a reference to the file it came from
     *
     * @param string      $key       The name of the file
     * @param mixed       $data      The data to save the
     * @param string      $filePath  The relative file path from the root of the website
     * @param string|null $namespace The name of the collection this data belongs to, if any
     */
    public function saveToTracker ($key, $data, $filePath, $namespace = null);

    /**
     * Remove an entry from the tracked items array
     *
     * @param mixed $trackedItem
     */
    public function delFromTracker ($trackedItem, $namespace = null);
}