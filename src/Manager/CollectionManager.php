<?php

/**
 * @copyright 2016 Vladimir Jimenez
 * @license   https://github.com/allejo/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Manager;

use allejo\stakx\Object\ContentItem;

/**
 * Class CollectionManager
 *
 * @package allejo\stakx\Manager
 */
class CollectionManager extends TrackingManager
{
    /**
     * A copy of the collection definitions to be available for later usage
     *
     * @var string[][]
     */
    private $collectionDefinitions;

    /**
     * @param  string $filePath
     *
     * @return ContentItem
     */
    public function &getContentItem ($filePath)
    {
        return $this->trackedItemsFlattened[$filePath];
    }

    /**
     * Get all of the Content Items grouped by Collection
     *
     * @return ContentItem[][]
     */
    public function &getCollections ()
    {
        return $this->trackedItems;
    }

    /**
     * Get the name of the Collection this Content Item belongs to
     *
     * @param  string $filePath
     *
     * @return string
     */
    public function getTentativeCollectionName ($filePath)
    {
        foreach ($this->collectionDefinitions as $collection)
        {
            if (strpos($filePath, $collection['folder']) === 0)
            {
                return $collection['name'];
            }
        }

        return '';
    }

    /**
     * Parse every collection and store them in the manager
     *
     * @param string[][] $collections An array of definitions for collections
     */
    public function parseCollections ($collections)
    {
        if ($collections === null)
        {
            $this->output->debug("No collections found, nothing to parse.");
            return;
        }

        $this->collectionDefinitions = $collections;

        /**
         * The information which each collection has taken from the configuration file
         *
         * $collection['name']      string The name of the collection
         *            ['folder']    string The folder where this collection has its ContentItems
         *
         * @var $collection array
         */
        foreach ($collections as $collection)
        {
            $this->output->notice("Loading '{$collection['name']}' collection...");

            $collectionFolder = $this->fs->absolutePath($collection['folder']);

            if (!$this->fs->exists($collectionFolder))
            {
                $this->output->warning("The folder '{$collection['folder']}' could not be found for the '{$collection['name']}' collection");
                continue;
            }

            $this->saveFolderDefinition($collection['folder'], $collection);
            $this->scanTrackableItems($collectionFolder, array(
                'namespace' => $collection['name']
            ));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function createNewItem($filePath)
    {
        $collection = $this->getTentativeCollectionName($filePath);

        return $this->handleTrackableItem($filePath, array(
            'namespace' => $collection
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function refreshItem($filePath)
    {
        return;
    }

    /**
     * {@inheritdoc}
     */
    protected function handleTrackableItem($filePath, $options = array())
    {
        $collectionName = $options['namespace'];

        $contentItem = new ContentItem($filePath);
        $contentItem->setCollection($collectionName);

        $this->addObjectToTracker($contentItem, $contentItem->getName(), $collectionName);

        $this->output->info(sprintf(
            "Loading ContentItem into '%s' collection: %s",
            $collectionName,
            $this->fs->getRelativePath($filePath)
        ));

        return $contentItem;
    }
}