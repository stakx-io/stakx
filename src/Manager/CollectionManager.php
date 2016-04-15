<?php

namespace allejo\stakx\Manager;

use allejo\stakx\Object\ContentItem;
use Symfony\Component\Finder\SplFileInfo;

class CollectionManager extends ItemManager
{
    /**
     * @var ContentItem[][]
     */
    private $collections;

    public function __construct()
    {
        parent::__construct();

        $this->collections = array();
    }

    public function getCollections ()
    {
        return $this->collections;
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
         *            ['permalink'] string The URL pattern each ContentItem will have
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
                $fileHash = substr(sha1($filePath), 0, 7);

                $this->collections[$collection['name']][$fileHash] = new ContentItem($filePath);

                $this->output->info(sprintf(
                    "Loading ContentItem into '%s' collection: %s",
                    $collection['name'],
                    $this->fs->appendPath($collection['folder'], $file->getRelativePathname())
                ));
            }
        }
    }
}