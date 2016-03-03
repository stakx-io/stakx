<?php

namespace allejo\stakx\Object;

use allejo\stakx\Core\Configuration;
use allejo\stakx\Environment\Filesystem;

class Website
{
    /**
     * @var Configuration
     */
    private $configuration;

    /**
     * @var ContentItem[]
     */
    private $collections;
    private $templates;
    private $pages;
    private $fs;

    public function __construct()
    {
        $this->fs = new Filesystem();
    }

    public function build ()
    {
        $this->parseCollections();

        echo count($this->collections);
    }

    /**
     * @return Configuration
     */
    public function getConfiguration()
    {
        return $this->configuration;
    }

    /**
     * @param string $configFile
     */
    public function setConfiguration ($configFile)
    {
        $this->configuration = new Configuration($configFile);
    }

    /**
     * @param ContentItem[] $contentItems
     */
    public function setCollections ($contentItems)
    {
        $this->collections = $contentItems;
    }

    public function setTemplates ($templates)
    {
        $this->templates = $templates;
    }

    public function setPages ($pages)
    {
        $this->pages = $pages;
    }

    /**
     * Parse all of the collections' front matter and content
     */
    private function parseCollections ()
    {
        $collections = $this->configuration->getCollectionsFolders();
        $this->collections = array();

        foreach ($collections as $collection)
        {
            $dataFiles = $this->fs->ls($collection['folder'])['files'];

            foreach ($dataFiles as $dataFile)
            {
                $this->collections[] = new ContentItem($dataFile);
            }
        }
    }
}