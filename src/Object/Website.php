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
     * @var PostItem[]
     */
    private $collections;
    private $templates;
    private $errors;
    private $pages;
    private $fs;

    public function __construct()
    {
        $this->fs = new Filesystem();
    }

    public function build (&$errorsCollection)
    {
        $this->errors = &$errorsCollection;

        $this->parseCollections();
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
     * @param PostItem[] $contentItems
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
            if ($this->fs->exists($collection['folder']))
            {
                $dataFiles = $this->fs->ls($collection['folder']);

                foreach ($dataFiles['files'] as $dataFile)
                {
                    $this->collections[] = new ContentItem($dataFile);
                }
            }
            else
            {
                $this->errors[] = sprintf("Warning: The '%s' collection cannot find: `%s`", $collection['name'], $collection['folder']);
            }
        }
    }
}