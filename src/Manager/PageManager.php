<?php

namespace allejo\stakx\Manager;

use allejo\stakx\Object\ContentItem;
use allejo\stakx\Object\PageView;
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

            $finder = $this->fs->getFinder(array(), array(), $pageViewFolder);
            $finder->name('/\.(html|twig)/');

            foreach ($finder as $file)
            {
                $newPageView = new PageView($file);
                $namespace = ($newPageView->isDynamicPage()) ? 'dynamic' : 'static';

                $this->addObjectToTracker($newPageView, $newPageView->getRelativeFilePath(), $namespace);
                $this->saveTrackerOptions($newPageView->getRelativeFilePath(), array(
                    'viewType' => $namespace
                ));

                if (!$newPageView->isDynamicPage())
                {
                    $this->addToSiteMenu($newPageView->getFrontMatter());
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
     * @param ContentItem[][] $collections
     */
    public function prepareDynamicPageViews ($collections)
    {
        if (empty($collections)) { return; }

        $this->collections = $collections;

        /** @var PageView $pageView */
        foreach ($this->trackedItems['dynamic'] as &$pageView)
        {
            $frontMatter = $pageView->getFrontMatter(false);
            $collection = $frontMatter['collection'];

            /** @var $item ContentItem */
            foreach ($collections[$collection] as &$item)
            {
                $item->evaluateFrontMatter($frontMatter);
                $pageView->addContentItem($item);
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

        foreach (array_keys($this->trackedItemsFlattened) as $filePath)
        {
            $this->compilePageView($filePath);
        }
    }

    /**
     * @param ContentItem $contentItem
     */
    public function compileContentItem (&$contentItem)
    {
        $pageView = $contentItem->getPageView();
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
    protected function handleTrackableItem($filePath, $options = array())
    {
        $this->compilePageView($filePath, true);
    }

    private function compilePageView ($filePath, $refresh = false)
    {
        if (!$this->isTracked($filePath))
        {
            throw new \Exception('PageView not found');
        }

        /** @var PageView $pageView */
        $pageView = &$this->trackedItemsFlattened[$filePath];
        $viewType = $this->trackedItemsOptions[$filePath]['viewType'];

        if ($refresh)
        {
            $pageView->refreshFileContent();
        }

        if ($viewType === 'static')
        {
            $this->compileStaticPageView($pageView);
        }
        else if ($viewType === 'dynamic')
        {
            $this->compileDynamicPageView($pageView);
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
     * @param PageView $pageView
     */
    private function compileStaticPageView (&$pageView)
    {
        $this->twig->addGlobal('__currentTemplate', $pageView->getFilePath());

        $template = $this->createTemplate($pageView);
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
            $e->setTemplateFile($pageView->getRelativeFilePath());

            throw $e;
        }
    }
}