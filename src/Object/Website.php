<?php

namespace allejo\stakx\Object;

use allejo\stakx\Core\TwigMarkdownEngine;
use allejo\stakx\Manager\PageManager;
use allejo\stakx\System\Filesystem;
use allejo\stakx\Manager\CollectionManager;
use allejo\stakx\Manager\DataManager;
use allejo\stakx\Twig\FilesystemExtension;
use allejo\stakx\Twig\TwigExtension;
use Aptoma\Twig\Extension\MarkdownExtension;
use Psr\Log\LoggerInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Twig_Environment;
use Twig_Loader_Filesystem;

class Website
{
    /**
     * The Twig environment that will be used to render pages. This includes all of the loaded extensions and global
     * variables.
     *
     * @var Twig_Environment
     */
    protected $twig;

    /**
     * The main configuration to be used to build the specified website
     *
     * @var Configuration
     */
    private $configuration;

    /**
     * An array of the assorted ContentItems belonging to specific collection
     *
     * @var ContentItem[]
     */
    private $collections;

    /**
     * When set to true, Twig templates will not have access to filters or functions which provide access to the
     * filesystem
     *
     * @var bool
     */
    private $safeMode;

    /**
     * @var array
     */
    private $siteMenu;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $logger;

    /**
     * @var
     */
    private $dataItems;

    /**
     * @var CollectionManager
     */
    private $cm;

    /**
     * @var DataManager
     */
    private $dm;

    /**
     * @var Filesystem
     */
    private $fs;

    /**
     * @var PageManager
     */
    private $pm;

    /**
     * Website constructor.
     *
     * @param LoggerInterface $logger
     */
    public function __construct (LoggerInterface $logger)
    {
        $this->cm = new CollectionManager();
        $this->dm = new DataManager();
        $this->pm = new PageManager();
        $this->fs = new Filesystem();
        $this->logger = $logger;
    }

    /**
     * Compile the website.
     */
    public function build ()
    {
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

        // Handle the site menu
        $this->siteMenu = $this->pm->getSiteMenu();

        // Configure the environment
        $this->createFolderStructure();
        $this->configureTwig();

        // Compile everything
        $this->pm->compile(
            $this->twig,
            $this->collections,
            $this->getConfiguration()->getTargetFolder()
        );
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
        $loader   = new Twig_Loader_Filesystem(array(
            getcwd()
        ));
        $theme    = $this->configuration->getTheme();
        $mdEngine = new TwigMarkdownEngine();

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
            'autoescape' => $this->getConfiguration()->getTwigAutoescape(),
            'cache'      => '.stakx-cache/twig'
        ));

        $this->twig->addGlobal('site', $this->configuration->getConfiguration());
        $this->twig->addGlobal('collections', $this->collections);
        $this->twig->addGlobal('menu', $this->siteMenu);
        $this->twig->addGlobal('data', $this->dataItems);
        $this->twig->addExtension(new TwigExtension());
        $this->twig->addExtension(new \Twig_Extensions_Extension_Text());
        $this->twig->addExtension(new MarkdownExtension($mdEngine));

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