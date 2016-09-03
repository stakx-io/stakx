<?php

namespace allejo\stakx\Manager;

use allejo\stakx\Object\ContentItem;
use Symfony\Component\Finder\SplFileInfo;

class CollectionManager extends ItemManager
{
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
        if ($this->isContentItem($filePath))
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

    public function isContentItem ($filePath)
    {
        $this->flattenCollections();

        $contentItemId = $this->fs->getBaseName($filePath);

        return (array_key_exists($contentItemId, $this->collectionsFlat));
    }

    public function parseCollections ($folders)
    {
        if ($folders === null)
        {
            $this->output->debug("No collections found, nothing to parse.");
            return;
        }

        /**
         * The information which each collection has taken from the configuration file
         *
         * $collection['name']      string The name of the collection
         *            ['folder']    string The folder where this collection has its ContentItems
         *
         * @var $collection array
         */
        foreach ($folders as $collection)
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
                $fileName = $this->fs->getBaseName($filePath);

                $contentItem = new ContentItem($filePath);
                $contentItem->setCollection($collection['name']);

                $this->collections[$collection['name']][$fileName] = $contentItem;

                $this->output->info(sprintf(
                    "Loading ContentItem into '%s' collection: %s",
                    $collection['name'],
                    $this->fs->appendPath($collection['folder'], $file->getRelativePathname())
                ));
            }
        }
    }

    private function flattenCollections ()
    {
        if (empty($this->collectionsFlat) && !empty($this->collections))
        {
            $this->collectionsFlat = call_user_func_array('array_merge', $this->collections);
        }
    }
}