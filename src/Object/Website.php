<?php

namespace allejo\stakx\Object;

use allejo\stakx\Core\Configuration;
use allejo\stakx\Environment\Filesystem;
use Twig_Environment;
use Twig_Loader_Filesystem;

class Website
{
    /**
     * @var Twig_Environment
     */
    protected $twig;

    /**
     * @var Configuration
     */
    private $configuration;

    /**
     * @var ContentItem[]
     */
    private $collections;

    /**
     * @var PageView
     */
    private $pageViews;
    private $errors;
    private $fs;

    public function __construct()
    {
        $this->fs = new Filesystem();
    }

    public function build (&$errorsCollection)
    {
        $this->errors = &$errorsCollection;

        $this->parseCollections();
        $this->parsePageViews();
        $this->configureTwig();
        $this->compilePageViews();
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
     * Parse all of the collections' front matter and content
     */
    private function parseCollections ()
    {
        $collections = $this->configuration->getCollectionsFolders();
        $this->collections = array();

        foreach ($collections as $collection)
        {
            if (!$this->fs->exists($collection['folder']))
            {
                $this->errors[] = sprintf("Warning: The '%s' collection cannot find: `%s`", $collection['name'], $collection['folder']);

                continue;
            }

            $dataFiles = $this->fs->ls($collection['folder']);

            foreach ($dataFiles['files'] as $dataFile)
            {
                $this->collections[$collection['name']][] = new ContentItem($dataFile);
            }
        }
    }

    private function parsePageViews ()
    {
        $pageViews = $this->getConfiguration()->getPageViews();
        $this->pageViews = array();

        foreach ($pageViews as $pageView)
        {
            if (!$this->fs->exists($pageView))
            {
                $this->errors[] = sprintf("Warning: The '%s' PageView folder cannot be found", $pageView);

                continue;
            }

            $viewFiles = $this->fs->ls($pageView);

            foreach ($viewFiles['files'] as $viewFile)
            {
                $this->pageViews[] = new PageView($viewFile);
            }
        }
    }

    private function compilePageViews()
    {
        /**
         * @var $pageView PageView
         */
        foreach ($this->pageViews as $pageView)
        {
            if ($pageView->isDynamicPage())
            {

            }
            else
            {
                $template = $this->twig->createTemplate($pageView->getContent());

                $output = $template->render(array(
                            "page" => "hello",
                            "site" => array()
                          ));

                echo $output . "\n";
            }
        }
    }

    private function configureTwig ()
    {
        $loader = new Twig_Loader_Filesystem(array(
            '_themes/bootstrap/',
            '.'
        ));
        $this->twig = new Twig_Environment($loader, array(
            'cache' => '.stakx-cache/twig'
        ));
    }
}