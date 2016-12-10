<?php

namespace allejo\stakx\Manager;

use allejo\stakx\Exception\TrackedItemNotFoundException;
use allejo\stakx\FrontMatter\ExpandedValue;
use allejo\stakx\Object\ContentItem;
use allejo\stakx\Object\DynamicPageView;
use allejo\stakx\Object\PageView;
use allejo\stakx\Object\RepeaterPageView;
use allejo\stakx\System\FileExplorer;
use allejo\stakx\System\Folder;
use Twig_Error_Syntax;
use Twig_Template;

/**
 * This class is responsible for handling all of the PageViews within a website.
 *
 * PageManager will parse all available dynamic and static PageViews. After, dynamic PageViews will be prepared by
 * setting the appropriate values for each ContentItem such as permalinks. Lastly, this class will compile all of the
 * PageViews and write them to the target directory.
 *
 * @package allejo\stakx\Manager
 */
class PageManager extends TrackingManager
{
    /**
     * The relative (to the stakx project) file path to the redirect template
     *
     * @var string|bool
     */
    private $redirectTemplate;

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

        $this->siteMenu = array();
    }

    public function setCollections (&$collections)
    {
        if (empty($collections)) { return; }

        $this->collections = &$collections;
    }

    public function setRedirectTemplate ($filePath)
    {
        $this->redirectTemplate = $filePath;
    }

    /**
     * @param Folder $folder The relative target directory as specified from the configuration file
     */
    public function setTargetFolder (&$folder)
    {
        $this->targetDir = &$folder;
    }

    public function configureTwig ($configuration, $options)
    {
        $twig = new TwigManager();
        $twig->configureTwig($configuration, $options);

        $this->twig = TwigManager::getInstance();
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

            // @TODO Replace this with a regular expression or have wildcard support
            $this->scanTrackableItems($pageViewFolder, array(
                'refresh' => false,
                'fileExplorer' => FileExplorer::INCLUDE_ONLY_FILES
            ), array('.html', '.twig'));
            $this->saveFolderDefinition($pageViewFolderName);
        }
    }

    /**
     * Compile dynamic and static PageViews
     */
    public function compileAll ()
    {
        foreach (array_keys($this->trackedItemsFlattened) as $filePath)
        {
            $this->compileFromFilePath($filePath);
        }
    }

    public function compileSome ($filter = array())
    {
        /** @var PageView $pageView */
        foreach ($this->trackedItemsFlattened as $pageView)
        {
            if ($pageView->hasTwigDependency($filter['namespace'], $filter['dependency']))
            {
                $this->compilePageView($pageView);
            }
        }
    }

    /**
     * @param ContentItem $contentItem
     */
    public function compileContentItem (&$contentItem)
    {
        $pageView = $contentItem->getPageView();

        // This ContentItem doesn't have an individual PageView dedicated to displaying this item
        if (is_null($pageView))
        {
            return;
        }

        $template = $this->createTemplate($pageView);
        $contentItem->evaluateFrontMatter(
            $pageView->getFrontMatter(false)
        );

        $output = $template->render(array(
            'this' => $contentItem
        ));

        $this->targetDir->writeFile($contentItem->getTargetFile(), $output);
    }

    /**
     * Add a new ContentItem to the respective parent PageView of the ContentItem
     *
     * @param ContentItem $contentItem
     */
    public function updatePageView ($contentItem)
    {
        /** @var DynamicPageView $pageView */
        foreach ($this->trackedItems['dynamic'] as &$pageView)
        {
            $fm = $pageView->getFrontMatter(false);

            if ($fm['collection'] == $contentItem->getCollection())
            {
                $pageView->addContentItem($contentItem);
            }
        }
    }

    /**
     * Update an existing Twig variable that's injected globally
     *
     * @param string $variable
     * @param string $value
     */
    public function updateTwigVariable ($variable, $value)
    {
        $this->twig->addGlobal($variable, $value);
    }

    /**
     * {@inheritdoc}
     */
    public function refreshItem($filePath)
    {
        $this->compileFromFilePath($filePath, true);
    }

    /**
     * {@inheritdoc}
     */
    protected function handleTrackableItem($filePath, $options = array())
    {
        $pageView  = PageView::create($filePath);
        $namespace = $pageView->getType();

        if ($namespace == PageView::DYNAMIC_TYPE)
        {
            $frontMatter = $pageView->getFrontMatter(false);
            $collection = $frontMatter['collection'];

            foreach ($this->collections[$collection] as &$item)
            {
                $item->evaluateFrontMatter($frontMatter);
                $pageView->addContentItem($item);
            }
        }

        $this->addObjectToTracker($pageView, $pageView->getRelativeFilePath(), $namespace);
        $this->saveTrackerOptions($pageView->getRelativeFilePath(), array(
            'viewType' => $namespace
        ));

        if ($namespace == PageView::STATIC_TYPE)
        {
            $this->addToSiteMenu($pageView);
        }
    }

    /**
     * Compile a given PageView
     *
     * @param string $filePath The file path to the PageView to compile
     * @param bool   $refresh  When set to true, the PageView will reread its contents
     *
     * @throws \Exception
     */
    private function compileFromFilePath ($filePath, $refresh = false)
    {
        if (!$this->isTracked($filePath))
        {
            throw new TrackedItemNotFoundException('PageView not found');
        }

        /** @var PageView $pageView */
        $pageView = &$this->trackedItemsFlattened[$filePath];

        $this->compilePageView($pageView, $refresh);
    }

    /**
     * @param DynamicPageView|PageView|RepeaterPageView $pageView
     * @param bool                                      $refresh
     */
    private function compilePageView ($pageView, $refresh = false)
    {
        if ($refresh)
        {
            $pageView->refreshFileContent();
        }

        switch ($pageView->getType())
        {
            case PageView::REPEATER_TYPE:
                $this->compileRepeaterPageView($pageView);
                $this->compileExpandedRedirects($pageView);
                break;

            case PageView::DYNAMIC_TYPE:
                $this->compileDynamicPageView($pageView);
                $this->compileNormalRedirects($pageView);
                break;

            case PageView::STATIC_TYPE:
                $this->compileStaticPageView($pageView);
                $this->compileNormalRedirects($pageView);
                break;
        }
    }

    /**
     * @param RepeaterPageView $pageView
     */
    private function compileRepeaterPageView (&$pageView)
    {
        $template = $this->createTemplate($pageView);

        foreach ($pageView->getRepeaterPermalinks() as $permalink)
        {
            $pageView->bumpPermalink();
            $pageView->setFrontMatter(array(
                'permalink' => $permalink->getEvaluated(),
                'iterators' => $permalink->getIterators()
            ));

            $output = $template->render(array(
                'this' => $pageView->createJail()
            ));

            $this->output->notice("Writing repeater file: {file}", array('file' => $pageView->getTargetFile()));
            $this->targetDir->writeFile($pageView->getTargetFile(), $output);
        }
    }

    /**
     * @param PageView $pageView
     */
    private function compileDynamicPageView (&$pageView)
    {
        $template = $this->createTemplate($pageView);

        $pageViewFrontMatter = $pageView->getFrontMatter(false);
        $collection = $pageViewFrontMatter['collection'];

        /** @var ContentItem $contentItem */
        foreach ($this->collections[$collection] as &$contentItem)
        {
            $output = $template->render(array(
                'this' => $contentItem->createJail()
            ));

            $this->output->notice("Writing file: {file}", array('file' => $contentItem->getTargetFile()));
            $this->targetDir->writeFile($contentItem->getTargetFile(), $output);
        }
    }

    /**
     * @param PageView $pageView
     */
    private function compileStaticPageView (&$pageView)
    {
        $this->twig->addGlobal('__currentTemplate', $pageView->getFilePath());

        $template = $this->createTemplate($pageView);
        $output = $template->render(array(
            'this' => $pageView->createJail()
        ));

        $this->output->notice("Writing file: {file}", array('file' => $pageView->getTargetFile()));
        $this->targetDir->writeFile($pageView->getTargetFile(), $output);
    }

    /**
     * @param DynamicPageView|PageView $pageView
     */
    private function compileNormalRedirects (&$pageView)
    {
        foreach ($pageView->getRedirects() as $redirect)
        {
            $redirectPageView = PageView::createRedirect(
                $redirect,
                $pageView->getPermalink(),
                $this->redirectTemplate
            );

            $this->compilePageView($redirectPageView);
        }
    }

    /**
     * @param RepeaterPageView $pageView
     */
    private function compileExpandedRedirects (&$pageView)
    {
        $permalinks = $pageView->getRepeaterPermalinks();

        /** @var ExpandedValue[] $repeaterRedirect */
        foreach ($pageView->getRepeaterRedirects() as $repeaterRedirect)
        {
            /**
             * @var int           $index
             * @var ExpandedValue $redirect
             */
            foreach ($repeaterRedirect as $index => $redirect)
            {
                $redirectPageView = PageView::createRedirect(
                    $redirect->getEvaluated(),
                    $permalinks[$index]->getEvaluated(),
                    $this->redirectTemplate
                );

                $this->compilePageView($redirectPageView);
            }
        }
    }

    /**
     * Add a static PageView to the menu array. Dynamic PageViews are not added to the menu
     *
     * @param PageView $pageView
     */
    private function addToSiteMenu (&$pageView)
    {
        $frontMatter = $pageView->getFrontMatter();

        if (!array_key_exists('permalink', $frontMatter) ||
            (array_key_exists('menu', $frontMatter) && !$frontMatter['menu']))
        {
            return;
        }

        $url = $pageView->getPermalink();
        $root = &$this->siteMenu;
        $permalink = trim($url, DIRECTORY_SEPARATOR);
        $dirs = explode(DIRECTORY_SEPARATOR, $permalink);

        while (count($dirs) > 0)
        {
            $name = array_shift($dirs);
            $name = (!empty($name)) ? $name : '.';

            if (!is_null($name) && count($dirs) == 0)
            {
                $children = array();

                if (array_key_exists($name, $root) && is_array($root[$name]))
                {
                    $children = $root[$name]['children'];
                }

                $root[$name] = &$pageView;
                $root = &$root[$name]->getChildren();

                if (!empty($children))
                {
                    $root = $children;
                }
            }
            else
            {
                $root[$name]['children'] = array();
                $root = &$root[$name]['children'];
            }
        }
    }

    /**
     * @param PageView $pageView
     *
     * @return Twig_Template
     * @throws Twig_Error_Syntax
     */
    private function createTemplate ($pageView)
    {
        try
        {
            return $this->twig->createTemplate($pageView->getContent());
        }
        catch (Twig_Error_Syntax $e)
        {
            $e->setTemplateName($pageView->getRelativeFilePath());

            throw $e;
        }
    }
}