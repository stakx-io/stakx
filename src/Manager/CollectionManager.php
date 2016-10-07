<?php

namespace allejo\stakx\Manager;

use allejo\stakx\Object\ContentItem;

class CollectionManager extends TrackingManager
{
    /**
     * @var string[][]
     */
    private $collectionDefinitions;

    /**
     * @param  string $filePath
     *
     * @return ContentItem|null
     */
    public function &getContentItem ($filePath)
    {
        return $this->trackedItemsFlattened[$filePath];
    }

    public function getCollections ()
    {
        return $this->trackedItems;
    }

    public function getFlatCollections ()
    {
        return $this->trackedItemsFlattened;
    }

    /**
     * Check whether a given file path is inside a directory of a known Collection
     *
     * @param  string $filePath
     *
     * @return bool
     */
    public function belongsToCollection ($filePath)
    {
        return (!empty($this->getTentativeCollectionName($filePath)));
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

            $this->scanTrackableItems($collectionFolder, array(
                'namespace' => $collection['name']
            ));
        }
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

        $this->addObjectToTracker($contentItem, $collectionName);

        $this->output->info(sprintf(
            "Loading ContentItem into '%s' collection: %s",
            $collectionName,
            $this->fs->getRelativePath($filePath)
        ));

        return $contentItem;
    }
}