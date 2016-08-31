<?php

namespace allejo\stakx\Manager;

use allejo\stakx\Object\ContentItem;
use allejo\stakx\Object\PageView;
use allejo\stakx\System\Folder;
use Symfony\Component\Finder\Finder;

/**
 * This class is responsible for handling all of the PageViews within a website.
 *
 * PageManager will parse all available dynamic and static PageViews. After, dynamic PageViews will be prepared by
 * setting the appropriate values for each ContentItem such as permalinks. Lastly, this class will compile all of the
 * PageViews and write them to the target directory.
 *
 * @package allejo\stakx\Manager
 */
class PageManager extends ItemManager
{
    /**
     * @var PageView[]
     */
    private $dynamicPageViews;

    /**
     * @var PageView[]
     */
    private $staticPageViews;

    /**
     * @var ContentItem[][]
     */
    private $collections;

    /**
     * @var Folder
     */
    private $targetDir;

    private $siteMenu;

    /**
     * @var \Twig_Environment
     */
    private $twig;

    /**
     * PageManager constructor
     */
    public function __construct()
    {
        parent::__construct();

        $this->dynamicPageViews = array();
        $this->staticPageViews  = array();
    }

    /**
     * Set the Twig environment used by Stakx for all Twig templates
     *
     * @param \Twig_Environment $twig
     */
    public function setTwig (&$twig)
    {
        $this->twig = &$twig;
    }

    /**
     * An array representing the website's menu structure with children and grandchildren made from static PageViews
     *
     * @return array
     */
    public function getSiteMenu ()
    {
        return $this->siteMenu;
    }

    /**
     * Go through all of the PageView directories and create a respective PageView for each and classify them as a
     * dynamic or static PageView.
     *
     * @param $pageViewFolders
     */
    public function parsePageViews ($pageViewFolders)
    {
        if (empty($pageViewFolders)) { return; }

        /**
         * The name of the folder where PageViews are located
         *
         * @var $pageViewFolder string
         */
        foreach ($pageViewFolders as $pageViewFolderName)
        {
            $pageViewFolder = $this->fs->absolutePath($pageViewFolderName);

            if (!$this->fs->exists($pageViewFolder))
            {
                continue;
            }

            $finder = new Finder();
            $finder->files()
                   ->name('/\.(html|twig)/')
                   ->ignoreDotFiles(true)
                   ->ignoreUnreadableDirs()
                   ->in($pageViewFolder);

            foreach ($finder as $viewFile)
            {
                $newPageView = new PageView($viewFile);
                $file_id = $this->fs->getRelativePath($newPageView->getFilePath()->getPathName());

                if ($newPageView->isDynamicPage())
                {
                    $this->dynamicPageViews[$file_id] = $newPageView;
                }
                else
                {
                    $this->addToSiteMenu($newPageView->getFrontMatter());
                    $this->staticPageViews[$file_id] = $newPageView;
                }
            }
        }
    }

    /**
     * Go through all of the dynamic PageViews and prepare the necessary information for each one.
     *
     * For example, permalinks are dynamic generated based on FrontMatter so this function sets the permalink for each
     * ContentItem in a collection. This is called before dynamic PageViews are compiled in order to allow access to
     * this information to Twig by the time it is compiled.
     *
     * @param ContentItem[] $collections
     */
    public function prepareDynamicPageViews ($collections)
    {
        if (empty($collections)) { return; }

        $this->collections = $collections;

        foreach ($this->dynamicPageViews as $pageView)
        {
            $frontMatter = $pageView->getFrontMatter(false);
            $collection = $frontMatter['collection'];

            if (empty($collections[$collection]))
            {
                continue;
            }

            /** @var $item ContentItem */
            foreach ($collections[$collection] as $item)
            {
                $item->evaluateFrontMatter($frontMatter);
            }
        }
    }

    /**
     * Compile dynamic and static PageViews
     *
     * @param Folder $targetDir The relative target directory as specified from the configuration file
     */
    public function compileAll (&$targetDir)
    {
        $this->targetDir = $targetDir;

        $this->compileDynamicPageViews();
        $this->compileStaticPageViews();
    }

    /**
     * Compile a single PageView into the appropriate output path
     *
     * @param $filePath
     *
     * @return bool
     */
    public function compileSingle ($filePath)
    {
        if (array_key_exists($filePath, $this->staticPageViews))
        {
            $this->output->notice("Compiling static page: {file}", array('file' => $filePath));

            $this->staticPageViews[$filePath]->refreshFileContent();
            $this->compileStaticPageView($this->staticPageViews[$filePath]);

            return true;
        }
        else if (array_key_exists($filePath, $this->dynamicPageViews))
        {
            $this->output->notice("Compiling dynamic page: {file}", array('file' => $filePath));

            $this->dynamicPageViews[$filePath]->refreshFileContent();
            $this->compileDynamicPageView($this->dynamicPageViews[$filePath]);

            return true;
        }

        return false;
    }

    /**
     * A dynamic PageView is one that is built from a collection and each collection item deserves its own page. This
     * function goes through all of the dynamic PageViews and compiles each page
     */
    private function compileDynamicPageViews ()
    {
        foreach ($this->dynamicPageViews as $pageView)
        {
            $this->compileDynamicPageView($pageView);
        }
    }

    /**
     * A static PageView is built from a single Twig file and is not automatically rendered based on a collection's
     * content. This function goes through all of the static PageViews and compiles them.
     *
     * @throws \Exception
     */
    private function compileStaticPageViews ()
    {
        foreach ($this->staticPageViews as $pageView)
        {
            $this->compileStaticPageView($pageView);
        }
    }

    /**
     * @param ContentItem $pageView
     *
     * @throws \Exception
     * @throws \Throwable
     */
    private function compileDynamicPageView ($pageView)
    {
        $template = $this->twig->createTemplate($pageView->getContent());

        $pageViewFrontMatter = $pageView->getFrontMatter(false);
        $collection = $pageViewFrontMatter['collection'];

        /** @var $contentItem ContentItem */
        foreach ($this->collections[$collection] as $contentItem)
        {
            $output = $template->render(array(
                'this' => $contentItem
            ));

            $this->targetDir->writeFile($contentItem->getTargetFile(), $output);
        }
    }

    /**
     * @param ContentItem $pageView
     */
    private function compileStaticPageView ($pageView)
    {
        $this->twig->addGlobal('__currentTemplate', $pageView->getFilePath());

        $template = $this->twig->createTemplate($pageView->getContent());
        $output = $template->render(array(
            'this' => $pageView->getFrontMatter()
        ));

        $this->targetDir->writeFile($pageView->getTargetFile(), $output);
    }

    /**
     * Add a static PageView to the menu array. Dynamic PageViews are not added to the menu
     *
     * @param array $frontMatter
     */
    private function addToSiteMenu ($frontMatter)
    {
        if (!array_key_exists('permalink', $frontMatter) ||
            (array_key_exists('menu', $frontMatter) && !$frontMatter['menu']))
        {
            return;
        }

        $url = $frontMatter['permalink'];
        $root = &$this->siteMenu;
        $permalink = trim($url, DIRECTORY_SEPARATOR);
        $dirs = explode(DIRECTORY_SEPARATOR, $permalink);

        while (count($dirs) > 0)
        {
            $name = array_shift($dirs);
            $name = (!empty($name)) ? $name : '.';

            if (!isset($root[$name]) && !is_null($name) && count($dirs) == 0)
            {
                $link = (pathinfo($url, PATHINFO_EXTENSION) !== "") ? $url : $permalink . DIRECTORY_SEPARATOR;

                $root[$name] = array_merge($frontMatter, array(
                    "url"  => '/' . $link,
                    "children" => array()
                ));
            }

            $root = &$root[$name]['children'];
        }
    }
}