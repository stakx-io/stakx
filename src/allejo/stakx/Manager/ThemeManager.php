<?php

/**
 * @copyright 2017 Vladimir Jimenez
 * @license   https://github.com/allejo/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Manager;

use allejo\stakx\Filesystem\FilesystemLoader as fs;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
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
        $this->themeData = array(
            'exclude' => array(),
            'include' => array(),
        );

        if (!fs::exists($this->themeFolder))
        {
            throw new FileNotFoundException("The '${themeName}' theme folder could not be found.'");
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

        return $this->handleTrackableItem($relativeFilePath, array(
            'prefix' => $this->themeFolderRelative,
        ));
    }

    public function copyFiles()
    {
        $this->logger->notice('Copying theme files...');

        $this->scanTrackableItems(
            $this->themeFolder,
            array(
                'prefix' => $this->themeFolderRelative,
            ),
            array_merge(
                $this->includes,
                $this->themeData['include']
            ),
            array_merge(
                $this->excludes,
                $this->themeData['exclude']
            )
        );
    }
}
