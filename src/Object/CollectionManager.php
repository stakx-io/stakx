<?php

namespace allejo\stakx\Object;

use allejo\stakx\Environment\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

class CollectionManager
{
    private $fs;

    /**
     * @var ContentItem[][]
     */
    private $collections;

    public function __construct()
    {
        $this->fs = new Filesystem();
        $this->collections = array();
    }

    public function getCollections ()
    {
        return $this->collections;
    }

    public function parseCollections ($folders)
    {
        if ($folders === null) { return; }

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
            if (!$this->fs->exists($collection['folder']))
            {
                continue;
            }

            $finder = new Finder();
            $finder->files()
                   ->ignoreDotFiles(true)
                   ->ignoreUnreadableDirs()
                   ->in($collection['folder']);

            /** @var $file SplFileInfo */
            foreach ($finder as $file)
            {
                $filePath = $this->fs->buildPath($collection['folder'], $file->getRelativePathname());
                $fileHash = substr(sha1($filePath), 0, 7);

                $this->collections[$collection['name']][$fileHash] = new ContentItem($filePath);
            }
        }
    }
}