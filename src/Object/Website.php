<?php

namespace allejo\stakx\Object;

use allejo\stakx\Manager\PageManager;
use allejo\stakx\System\Filesystem;
use allejo\stakx\Manager\CollectionManager;
use allejo\stakx\Manager\DataManager;
use allejo\stakx\Twig\FilesystemExtension;
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
     * @var PageView[]
     */
    private $dynamicPageViews;

    /**
     * @var PageView[]
     */
    private $staticPageViews;

    /**
     * @var Configuration
     */
    private $configuration;

    /**
     * @var ContentItem[]
     */
    private $collections;

    private $safeMode;

    /**
     * @var array
     */
    private $siteMenu;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $logger;

    private $dataItems;

    private $cm;
    private $dm;
    private $pm;

    /**
     * @var \allejo\stakx\System\Filesystem
     */
    private $fs;

    public function __construct (LoggerInterface $logger)
    {
        $this->cm = new CollectionManager();
        $this->dm = new DataManager();
        $this->pm = new PageManager();
        $this->fs = new Filesystem();
        $this->logger = $logger;
    }

    public function build ()
    {
        $this->createFolderStructure();

        $messages = array();

        // Parse DataItems
        $this->dm->parseDataItems($this->getConfiguration()->getDataFolders(), $messages);
        $this->dm->parseDataSets($this->getConfiguration()->getDataSets(), $messages);
        $this->dataItems = $this->dm->getDataItems();

        // Prepare Collections
        $this->cm->parseCollections($this->getConfiguration()->getCollectionsFolders());
        $this->collections = $this->cm->getCollections();

        // Handle PageViews
        $this->pm->parsePageViews($this->getConfiguration()->getPageViewFolders());
        $this->pm->prepareDynamicPageViews($this->collections);
        $this->dynamicPageViews = $this->pm->getDynamicPageViews();
        $this->staticPageViews  = $this->pm->getStaticPageViews();
        $this->siteMenu = $this->pm->getSiteMenu();

        $this->configureTwig();
        $this->compileDynamicPageViews();
        $this->compileStaticPageViews();
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
        $this->configuration = new Configuration($configFile, $this->logger);
    }

    public function handleSingleFile ($filePath)
    {
        $filePath = ltrim($filePath, DIRECTORY_SEPARATOR);
        $pageViewDirs = $this->configuration->getPageViewFolders();
        $collectionDirs = array_map(function ($a) { return $a["folder"]; }, $this->configuration->getCollectionsFolders());

        $_paths = explode(DIRECTORY_SEPARATOR, $filePath);

        if (count($_paths) > 1 && (in_array($_paths[0], $collectionDirs) || in_array($_paths[0], $pageViewDirs)))
        {

        }
        else
        {
            $this->copyToCompiledSite($filePath);
        }
    }

    public function getSafeMode ()
    {
        return $this->safeMode;
    }

    public function setSafeMode ($bool)
    {
        $this->safeMode = $bool;
    }

    /**
     * Prepare the Stakx environment by creating necessary cache folders
     */
    private function createFolderStructure ()
    {
        $this->fs->remove(array(
            $this->fs->absolutePath('.stakx-cache'),
            $this->fs->absolutePath('_site')
        ));

        $this->fs->mkdir('.stakx-cache/twig');
    }

    /**
     * Configure the Twig environment used by Stakx. This includes loading themes, global variables, extensions, and
     * debug settings.
     *
     * @todo Load custom Twig extensions from _config.yml
     */
    private function configureTwig ()
    {
        $loader = new Twig_Loader_Filesystem(array(
            getcwd()
        ));
        $theme  = $this->configuration->getTheme();

        // Only load a theme if one is specified and actually exists
        if (!is_null($theme))
        {
            try
            {
                $loader->addPath($this->fs->absolutePath('_themes', $this->configuration->getTheme()));
            }
            catch (\Twig_Error_Loader $e)
            {
                $this->logger->error("The following theme could not be loaded: {theme}", array(
                    "theme" => $theme
                ));
                $this->logger->error($e->getMessage());
            }
        }

        $this->twig = new Twig_Environment($loader, array(
            'cache' => '.stakx-cache/twig'
        ));

        $this->twig->addGlobal('site', $this->configuration->getConfiguration());
        $this->twig->addGlobal('collections', $this->collections);
        $this->twig->addGlobal('menu', $this->siteMenu);
        $this->twig->addGlobal('data', $this->dataItems);
        $this->twig->addExtension(new TwigExtension());
        $this->twig->addExtension(new \Twig_Extensions_Extension_Text());

        if (!$this->safeMode)
        {
            $this->twig->addExtension(new FilesystemExtension());
        }

        if ($this->configuration->isDebug())
        {
            $this->twig->addExtension(new \Twig_Extension_Debug());
            $this->twig->enableDebug();
        }
    }

    /**
     * A dynamic PageView is one that is built from a collection and each collection item deserves its own page. This
     * function goes through all of the dynamic PageViews and compiles each page
     *
     * @throws \Exception
     */
    private function compileDynamicPageViews ()
    {
        foreach ($this->dynamicPageViews as $pageView)
        {
            $template = $this->twig->createTemplate($pageView->getContent());

            $this->logger->notice("Compiling collection items for dynamic PageView '{filePath}'", array(
                'filePath' => $pageView->getFilePath()
            ));

            $pageViewFrontMatter = $pageView->getFrontMatter(false);
            $collection = $pageViewFrontMatter['collection'];

            /** @var $contentItem ContentItem */
            foreach ($this->collections[$collection] as $contentItem)
            {
                $this->logger->info("Compiling PageView for '{filePath}' to '{targetPath}'", array(
                    'filePath' => $contentItem->getFilePath(),
                    'targetPath' => $contentItem->getTargetFile()
                ));

                $output = $template->render(array(
                    'page' => $pageViewFrontMatter,
                    'item' => $contentItem
                ));

                $this->fs->writeFile(
                    $this->getConfiguration()->getTargetFolder(),
                    $contentItem->getTargetFile(),
                    $output
                );
            }
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
            $template = $this->twig->createTemplate($pageView->getContent());

            $this->logger->notice("Compiling static PageView '{filePath}' to '{targetPath}'", array(
                'filePath' => $pageView->getFilePath(),
                'targetPath' => $pageView->getTargetFile()
            ));

            $output = $template->render(array(
                "page" => $pageView->getFrontMatter()
            ));

            $this->fs->writeFile(
                $this->getConfiguration()->getTargetFolder(),
                $pageView->getTargetFile(),
                $output
            );
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
               ->in(getcwd())
               ->notPath('/^_.*/');

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
        try
        {
            $this->fs->copy(
                $this->fs->absolutePath($filePath),
                $this->fs->absolutePath($this->getConfiguration()->getTargetFolder(), $filePath),
                true
            );
        }
        catch (\Exception $e)
        {
            $this->logger->error($e->getMessage());
        }
    }
}