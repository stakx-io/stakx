<?php

namespace allejo\stakx\Manager;

use Symfony\Component\Filesystem\Exception\FileNotFoundException;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\Yaml\Yaml;

class ThemeManager extends FileManager
{
    const THEME_DEFINITION_FILE = "stakx-theme.yml";

    private $themeFolder;
    private $themeFile;
    private $themeData;

    public function __construct ($themeName, $includes = array(), $excludes = array())
    {
        parent::__construct();

        $this->themeFolder = $this->fs->appendPath("_themes", $themeName);
        $this->themeFile   = $this->fs->absolutePath($this->themeFolder, self::THEME_DEFINITION_FILE);
        $this->themeData   = array(
            'exclude' => array(),
            'include'  => array()
        );

        if (!$this->fs->exists($this->themeFolder))
        {
            throw new FileNotFoundException("The '${themeName}' theme folder could not be found.'");
        }

        if ($this->fs->exists($this->themeFile))
        {
            $themeData = Yaml::parse(file_get_contents($this->themeFile));

            $this->themeData = array_merge_recursive($this->themeData, $themeData);
        }

        foreach ($this->themeData['include'] as &$include)
        {
            $include = $this->fs->appendPath($this->themeFolder, $include);
        }

        $this->finder = $this->fs->getFinder(
            array_merge(
                $includes,
                $this->themeData['include']
            ),
            array_merge(
                $excludes,
                $this->themeData['exclude'],
                array('.twig')
            ),
            $this->fs->absolutePath($this->themeFolder)
        );
    }

    public function copyFiles ()
    {
        $this->output->notice('Copying theme files...');

        /** @var SplFileInfo $file */
        foreach ($this->finder as $file)
        {
            $this->files[] = $file;
            $this->copyToCompiledSite($file, $this->themeFolder);
        }
    }
}