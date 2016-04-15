<?php

namespace allejo\stakx\Object;

use allejo\stakx\Core\ConsoleInterface;
use allejo\stakx\Core\TwigMarkdownEngine;
use allejo\stakx\Manager\PageManager;
use allejo\stakx\System\Filesystem;
use allejo\stakx\Manager\CollectionManager;
use allejo\stakx\Manager\DataManager;
use allejo\stakx\Twig\FilesystemExtension;
use allejo\stakx\Twig\TwigExtension;
use Aptoma\Twig\Extension\MarkdownExtension;
use Symfony\Component\Console\Output\OutputInterface;
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
     * @var
     */
    private $dataItems;

    /**
     * When set to true, the Stakx website will be built without a configuration file
     *
     * @var bool
     */
    private $confLess;

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
     * @var ConsoleInterface
     */
    private $output;

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
     * @param OutputInterface $output
     */
    public function __construct (OutputInterface $output)
    {
        $this->output = new ConsoleInterface($output);
        $this->cm = new CollectionManager();
        $this->dm = new DataManager();
        $this->pm = new PageManager();
        $this->fs = new Filesystem();
    }

    /**
     * Compile the website.
     */
    public function build ()
    {
        $messages = array();

        // Parse DataItems
        $this->dm->setOutput($this->output);
        $this->dm->parseDataItems($this->getConfiguration()->getDataFolders(), $messages);
        $this->dm->parseDataSets($this->getConfiguration()->getDataSets(), $messages);
        $this->dataItems = $this->dm->getDataItems();

        // Prepare Collections
        $this->cm->setOutput($this->output);
        $this->cm->parseCollections($this->getConfiguration()->getCollectionsFolders());
        $this->collections = $this->cm->getCollections();

        // Handle PageViews
        $this->pm->setOutput($this->output);
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
        $this->copyThemeAssets();
        $this->copyStaticFiles();

        $this->output->writeln("Your site built successfully! It can be found at: {$this->getConfiguration()->getTargetFolder()}" . DIRECTORY_SEPARATOR);
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
     * @param bool   $confLess
     */
    public function setConfiguration ($configFile)
    {
        if (!$this->fs->exists($configFile) && !$this->getConfLess())
        {
            $this->output->error("You are trying to build a website in a directory without a configuration file. Is this what you meant to do?");
            $this->output->error("To build a website without a configuration, use the '--no-conf' option");

            throw new \LogicException("Cannot build a website without a configuration when not in Configuration-less mode");
        }

        if ($this->getConfLess())
        {
            $configFile = "";
        }

        $this->configuration = new Configuration($configFile, $this->output);
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

    /**
     * Get whether or not the website is being built in Configuration-less mode
     *
     * @return bool True when being built with no configuration file
     */
    public function getConfLess ()
    {
        return $this->confLess;
    }

    /**
     * Set whether or not the website should be built with a configuration
     *
     * @param bool $status True when a website should be built without a configuration
     */
    public function setConfLess ($status)
    {
        $this->confLess = $status;
    }

    /**
     * Get whether or not the website is being built in safe mode.
     *
     * Safe mode is defined as disabling file system access from Twig and disabling user Twig extensions
     *
     * @return bool True when the website is being built in safe mode
     */
    public function getSafeMode ()
    {
        return $this->safeMode;
    }

    /**
     * Set whether a website should be built in safe mode
     *
     * @param bool $bool True if a website should be built in safe mode
     */
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
                $this->output->error("The following theme could not be loaded: {theme}", array(
                    "theme" => $theme
                ));
                $this->output->error($e->getMessage());
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
     * Copy static files from a theme to the compiled website
     */
    private function copyThemeAssets ()
    {
        $theme = $this->configuration->getTheme();

        if (is_null($theme))
        {
            return;
        }

        $themeFolder  = $this->fs->appendPath("_themes", $theme);
        $ignoreFile   = $this->fs->absolutePath($themeFolder, ".stakx-ignore");
        $ignoredFiles = array();

        if ($this->fs->exists($ignoreFile))
        {
            $ignoredFiles = explode(PHP_EOL, file_get_contents($ignoreFile));
        }

        $finder = $this->fs->getFinder(
            $this->getConfiguration()->getIncludes(),
            array_merge(
                $this->getConfiguration()->getExcludes(),
                $ignoredFiles,
                array('.twig')
            ),
            $this->fs->absolutePath($themeFolder)
        );

        /** @var SplFileInfo $file */
        foreach ($finder as $file)
        {
            $this->copyToCompiledSite($file, $themeFolder);
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
        $finder = $this->fs->getFinder(
            $this->getConfiguration()->getIncludes(),
            $this->getConfiguration()->getExcludes()
        );

        /** @var $file SplFileInfo */
        foreach ($finder as $file)
        {
            $this->copyToCompiledSite($file);
        }
    }

    /**
     * Copy a file from a the source directory to the compiled website directory. The exact relative path to the file
     * will be recreated in the compiled website directory.
     *
     * @param SplFileInfo $file   The relative path of the file to be copied
     * @param string      $prefix
     */
    private function copyToCompiledSite ($file, $prefix = "")
    {
        if (!$this->fs->exists($file)) { return; }

        $filePath = $file->getRealPath();
        $pathToStrip = $this->fs->appendPath(getcwd(), $prefix);
        $siteTargetPath = ltrim(str_replace($pathToStrip, "", $filePath), DIRECTORY_SEPARATOR);

        try
        {
            $this->fs->copy(
                $filePath,
                $this->fs->absolutePath($this->getConfiguration()->getTargetFolder(), $siteTargetPath),
                true
            );
        }
        catch (\Exception $e)
        {
            $this->output->error($e->getMessage());
        }
    }
}