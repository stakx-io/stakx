<?php

namespace allejo\stakx\Object;

use allejo\stakx\Core\Configuration;
use allejo\stakx\Environment\Filesystem;
use allejo\stakx\Twig\TwigExtension;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
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
     * @var PageView[]
     */
    private $pageViews;
    private $siteMenu;
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
        $this->copyStaticFiles();
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
                $newPageView = new PageView($viewFile);
                $fm = $newPageView->getFrontMatter();

                $this->addToSiteMenu($fm['title'], $newPageView->getPermalink());

                $this->pageViews[] = $newPageView;
            }
        }
    }

    private function addToSiteMenu ($pageName, $url)
    {
        $root = &$this->siteMenu;
        $permalink = trim($url, '/');
        $dirs = explode('/', $permalink);

        while (count($dirs) > 0)
        {
            $name = array_shift($dirs);
            $name = (!empty($name)) ? $name : '.';

            if (!isset($root[$name]) && !is_null($name) && count($dirs) == 0)
            {
                $link = (pathinfo($url, PATHINFO_EXTENSION) !== "") ? $url : $permalink . '/';

                $root[$name] = array(
                    "name" => $pageName,
                    "url"  => $link,
                    "children" => array()
                );
            }

            $root = &$root[$name]['children'];
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
                $twigInfo = array(
                    "page" => $pageView->getFrontMatter(),
                );
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
            //'cache' => '.stakx-cache/twig'
        ));

        $this->twig->addGlobal('site', $this->configuration->getConfiguration());
        $this->twig->addGlobal('collections', $this->collections);
        $this->twig->addGlobal('menu', $this->siteMenu);
        $this->twig->addExtension(new TwigExtension());

        if ($this->configuration->isDebug())
        {
            $this->twig->addExtension(new \Twig_Extension_Debug());
            $this->twig->enableDebug();
        }
    }

    /**
     * Copy the static files from the current directory into the compiled website directory.
     *
     * Static files are defined as follows:
     *   - Does not start with an underscore or is inside of a directory starting with an underscore
     *   - Does not start with a period or is inside of a directory starting with a period
     */
    private function copyStaticFiles ()
    {
        $finder = new Finder();
        $finder->files()
               ->ignoreDotFiles(true)
               ->ignoreUnreadableDirs()
               ->in('.')
               ->notPath('/_.*/');

        /** @var $file SplFileInfo */
        foreach ($finder as $file)
        {
            $this->copyToCompiledSite($file->getRelativePathname());
        }
    }

    /**
     * Copy a file from a the source directory to the compiled website directory. The exact relative path to the file
     * will be recreated in the compiled website directory.
     *
     * @param string $filePath The relative path of the file to be copied
     */
    private function copyToCompiledSite ($filePath)
    {
        $this->fs->copy(
            $filePath,
            $this->fs->buildPath($this->getConfiguration()->getTargetFolder(), $filePath),
            true
        );
    }
}