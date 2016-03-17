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
        $twigVariables = array(
            "site" => $this->configuration->getConfiguration(),
            "collections" => $this->collections
        );

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
                $twigInfo = array_merge($twigVariables, array(
                    "page" => $pageView->getFrontMatter(),
                ));
                $output   = $template->render($twigInfo);

                $this->fs->writeFile(
                    $this->getConfiguration()->getTargetFolder(),
                    $pageView->getTargetFile(),
                    $output
                );
            }
        }
    }

    private function configureTwig ()
    {
        // @todo Throw an error if theme is not found
        $loader = new Twig_Loader_Filesystem(array(
            sprintf('_themes/%s/', $this->configuration->getTheme()),
            '.'
        ));
        $this->twig = new Twig_Environment($loader, array(
            'cache' => '.stakx-cache/twig'
        ));

        if ($this->configuration->isDebug())
        {
            $this->twig->addExtension(new \Twig_Extension_Debug());
            $this->twig->enableDebug();
        }
    }
}