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
     * Save data to the tracker with a reference to the file it came from
     *
     * @param string      $key       The name of the file
     * @param mixed       $data      The data to save the
     * @param string      $filePath  The relative file path from the root of the website
     * @param string|null $namespace The name of the collection this data belongs to, if any
     */
    public function addArrayToTracker ($key, $data, $filePath, $namespace = null);

    /**
     * Add a FrontMatterObject based object to the tracker
     *
     * @param FrontMatterObject $trackedItem
     * @param string|null       $namespace
     *
     * @return
     */
    public function addObjectToTracker (&$trackedItem, $namespace = null);

    /**
     * Remove all data related to an array that was saved
     *
     * @param string      $key
     * @param string      $filePath
     * @param string|null $namespace
     */
    public function delArrayFromTracker ($key, $filePath, $namespace = null);

    /**
     * Remove an entry from the tracked items array
     *
     * @param mixed       $trackedItem
     * @param string|null $namespace
     */
    public function delObjectFromTracker ($trackedItem, $namespace = null);

    /**
     * Whether or not to enable tracking of files.
     *
     * Setting this to false will disable a lot of the overhead and caching done when a project is being watched
     *
     * @param bool $enabled
     */
    public function enableTracking ($enabled);

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
}