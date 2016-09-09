<?php

namespace allejo\stakx\Manager;

use allejo\stakx\Object\ContentItem;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;
use Symfony\Component\Finder\SplFileInfo;

class CollectionManager extends BaseManager
{
    /**
     * @var string[][]
     */
    private $collectionDefinitions;

    /**
     * @var ContentItem[]
     */
    private $collectionsFlat;

    /**
     * @var ContentItem[][]
     */
    private $collections;

    public function __construct ()
    {
        parent::__construct();

        $this->collections = array();
        $this->collectionsFlat = array();
    }

    /**
     * @param $filePath
     *
     * @return ContentItem|null
     */
    public function &getContentItem ($filePath)
    {
        if ($this->isTrackedByManager($filePath))
        {
            $contentItemId = $this->fs->getBaseName($filePath);

            return $this->collectionsFlat[$contentItemId];
        }

        return null;
    }

    public function getCollections ()
    {
        return $this->collections;
    }

    public function getFlatCollections ()
    {
        $this->flattenCollections();

        return $this->collectionsFlat;
    }

    /**
     * Check whether a given file path to a content item is already being tracked as part of a collection
     *
     * @param  string $filePath
     *
     * @return bool
     */
    public function isTrackedByManager ($filePath)
    {
        $this->flattenCollections();

        $contentItemId = $this->fs->getBaseName($filePath);

        return (array_key_exists($contentItemId, $this->collectionsFlat));
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

    public function addToCollection ($filePath)
    {
        $relativePath = $filePath;
        $filePath = $this->fs->absolutePath($filePath);

        if (!$this->fs->exists($filePath))
        {
            throw new FileNotFoundException(sprintf("Collection item to be added cannot be found: %s", $relativePath));
        }

        $collectionName = $this->getTentativeCollectionName($relativePath);
        $contentItem    = $this->addContentItemToCollection($filePath, $collectionName);
        $fileName       = $this->fs->getBaseName($contentItem->getRelativeFilePath());

        if (!empty($this->collectionsFlat))
        {
            $this->collectionsFlat[$fileName] = $contentItem;
        }
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

            $finder = $this->fs->getFinder(array(), array(), $collectionFolder);

            /** @var $file SplFileInfo */
            foreach ($finder as $file)
            {
                $filePath = $this->fs->appendPath($collectionFolder, $file->getRelativePathname());

                $this->addContentItemToCollection($filePath, $collection['name']);
            }
        }
    }

    private function addContentItemToCollection ($filePath, $collectionName)
    {
        $fileName = $this->fs->getBaseName($filePath);

        $contentItem = new ContentItem($filePath);
        $contentItem->setCollection($collectionName);

        $this->collections[$collectionName][$fileName] = $contentItem;

        $this->output->info(sprintf(
            "Loading ContentItem into '%s' collection: %s",
            $collectionName,
            $this->fs->getRelativePath($filePath)
        ));

        return $contentItem;
    }

    private function flattenCollections ()
    {
        if (empty($this->collectionsFlat) && !empty($this->collections))
        {
            $this->collectionsFlat = call_user_func_array('array_merge', $this->collections);
        }
    }
}