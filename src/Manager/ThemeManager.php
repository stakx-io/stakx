<?php

/**
 * @copyright 2018 Vladimir Jimenez
 * @license   https://github.com/stakx-io/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Manager;

use allejo\stakx\Filesystem\FileExplorerDefinition;
use allejo\stakx\Filesystem\FilesystemLoader as fs;
use allejo\stakx\Filesystem\Folder;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;
use Symfony\Component\Yaml\Yaml;

class ThemeManager extends AssetManager
{
    const THEME_FOLDER = '_themes';
    const THEME_DEFINITION_FILE = 'stakx-theme.yml';

    private $themeFolderRelative;
    private $themeFolder;
    private $themeFile;
    private $themeData;
    private $themeName;

    public function __construct($themeName, EventDispatcherInterface $eventDispatcher, LoggerInterface $logger)
    {
        parent::__construct($eventDispatcher, $logger);

        $this->themeFolderRelative = fs::appendPath(self::THEME_FOLDER, $themeName);
        $this->themeFolder = fs::absolutePath(self::THEME_FOLDER, $themeName);
        $this->themeName = $themeName;
        $this->themeFile = fs::appendPath($this->themeFolder, self::THEME_DEFINITION_FILE);
        $this->themeData = [
            'exclude' => [
                // Ignore underscore directories inside of our theme folder
                sprintf("/_themes\/%s\/_/", $this->themeName),
            ],
            'include' => [],
        ];

        if (!fs::exists($this->themeFolder))
        {
            throw new FileNotFoundException("The '{$themeName}' theme folder could not be found.'");
        }

        if (fs::exists($this->themeFile))
        {
            $themeData = Yaml::parse(file_get_contents($this->themeFile));

            $this->themeData = array_merge_recursive($this->themeData, $themeData);
        }

        foreach ($this->themeData['include'] as &$include)
        {
            $include = fs::appendPath($this->themeFolder, $include);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function refreshItem($filePath)
    {
        $relativeFilePath = str_replace($this->themeFolderRelative . '/', '', $filePath);

        return $this->handleTrackableItem(
            $relativeFilePath,
            $this->trackedItemsOptions[$filePath]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function shouldBeTracked($filePath)
    {
        $isThemeAsset = (substr($filePath, 0, strlen($this->themeFolderRelative)) === $this->themeFolderRelative);

        return $isThemeAsset && parent::shouldBeTracked($filePath);
    }

    /**
     * {@inheritdoc}
     */
    public function createNewItem($filePath)
    {
        $relativeFilePath = str_replace($this->themeFolderRelative . '/', '', $filePath);

        return $this->handleTrackableItem($relativeFilePath, [
            'prefix' => $this->themeFolderRelative,
        ]);
    }

    public function copyFiles()
    {
        $this->logger->notice('Copying theme files...');

        $folder = new Folder($this->themeFolder);

        $def = new FileExplorerDefinition($folder);
        $def->includes = array_merge(
            $this->includes,
            $this->themeData['include']
        );
        $def->excludes = array_merge(
            $this->excludes,
            $this->themeData['exclude']
        );

        $this->scanTrackableItems($def, [
            'prefix' => $this->themeFolderRelative,
        ]);
    }
}
