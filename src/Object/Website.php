<?php

namespace allejo\stakx\Object;

use allejo\stakx\Core\Configuration;
use allejo\stakx\Environment\Filesystem;
use allejo\stakx\Twig\TwigExtension;
use Psr\Log\LoggerInterface;
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
    private $logger;
    private $fs;

    public function __construct (LoggerInterface $logger)
    {
        $this->fs = new Filesystem();
        $this->logger = $logger;
    }

    public function build ()
    {
        $this->errors = array();

        $this->parseCollections();
        $this->parsePageViews();
        $this->makeCacheFolder();
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
     * Go through all of the collections and create respective ContentItems for each entry
     */
    private function parseCollections ()
    {
        $collections = $this->configuration->getCollectionsFolders();
        $this->collections = array();

        /**
         * The information which each collection has taken from the configuration file
         *
         * $collection['name']      string The name of the collection
         *            ['folder']    string The folder where this collection has its ContentItems
         *            ['permalink'] string The URL pattern each ContentItem will have
         *
         * @var $collection array
         */
        foreach ($collections as $collection)
        {
            if (!$this->fs->exists($collection['folder']))
            {
                $this->logger->warning("The '{name}' collection cannot find the following folder: `{folder}`", array(
                    "name"   => $collection['name'],
                    "folder" => $collection['folder']
                ));

                continue;
            }

            $finder = new Finder();
            $finder->files()
                   ->ignoreDotFiles(true)
                   ->ignoreUnreadableDirs()
                   ->in($collection['folder']);

            $this->logger->notice("Loading collection: {name}", array(
                "name" => $collection['name']
            ));

            /** @var $file SplFileInfo */
            foreach ($finder as $file)
            {
                $filePath = $this->fs->buildPath($collection['folder'], $file->getRelativePathname());

                $this->logger->info("  Found entry: {file}", array(
                    "file" => $filePath
                ));

                $this->collections[$collection['name']][] = new ContentItem($filePath);
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
                $frontMatter = $newPageView->getFrontMatter();

                $this->addToSiteMenu($frontMatter);

                $this->pageViews[] = $newPageView;
            }
        }
    }

    private function addToSiteMenu ($frontMatter)
    {
        if (!array_key_exists('permalink', $frontMatter) ||
            (array_key_exists('menu', $frontMatter) && !$frontMatter['menu']))
        {
            return;
        }

        $url = $frontMatter['permalink'];
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

                $root[$name] = array_merge($frontMatter, array(
                    "url"  => '/' . $link,
                    "children" => array()
                ));
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

    private function makeCacheFolder ()
    {
        if (!$this->fs->exists('.stakx-cache'))
        {
            $this->fs->mkdir('.stakx-cache/twig');
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