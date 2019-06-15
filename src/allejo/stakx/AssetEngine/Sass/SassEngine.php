<?php

/**
 * @copyright 2018 Vladimir Jimenez
 * @license   https://github.com/stakx-io/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\AssetEngine\Sass;

use __;
use allejo\stakx\AssetEngine\AssetEngineInterface;
use allejo\stakx\Configuration;
use allejo\stakx\Document\BasePageView;
use allejo\stakx\Document\StaticPageView;
use allejo\stakx\Filesystem\FilesystemLoader as fs;
use allejo\stakx\Filesystem\Folder;
use allejo\stakx\Manager\PageManager;
use allejo\stakx\Service;
use ScssPhp\ScssPhp\Formatter\Compact;
use ScssPhp\ScssPhp\Formatter\Crunched;
use ScssPhp\ScssPhp\Formatter\Expanded;
use ScssPhp\ScssPhp\Formatter\Nested;

class SassEngine implements AssetEngineInterface
{
    const CACHE_FILE = 'sass_engine.cache';

    private $fileSourceMap = false;
    private $configuration;
    /** @var PageManager */
    private $pageManager;
    private $compiler;
    private $options = [];

    public function __construct(Configuration $configuration)
    {
        $this->configuration = $configuration;
        $this->compiler = new Compiler();
    }

    public function getName()
    {
        return 'Sass';
    }

    public function getConfigurationNamespace()
    {
        return 'scss';
    }

    public function getDefaultConfiguration()
    {
        return [
            'style' => 'compressed',
            'sourcemap' => false,
        ];
    }

    public function getFolder()
    {
        return '_sass';
    }

    public function getExtensions()
    {
        return ['scss'];
    }

    /**
     * @param string $content
     * @param $options = [
     *     'pageview' => new StaticPageView()
     * ]
     *
     * @return string
     */
    public function parse($content, array $options = [])
    {
        $sourceMapOptions = [
            'sourceMapBasepath' => Service::getWorkingDirectory(),
        ];

        $this->handleThemeImports($content);

        // We don't need to write the source map to a file
        if (!$this->fileSourceMap)
        {
            $this->compiler->setSourceMapOptions($sourceMapOptions);

            return $this->compiler->compile($content);
        }

        /** @var StaticPageView $pageView */
        $pageView = $options['pageview'];

        // Always put our source map next to the respective CSS file
        $sourceMapTargetPath = $this->getSourceMapTargetFile($pageView);
        $sourceMapFilename = fs::getFilename($sourceMapTargetPath);

        $sourceMapOptions = array_merge($sourceMapOptions, [
            'sourceMapFilename' => $sourceMapFilename,
            'sourceMapURL' => $pageView->getPermalink() . '.map',
            'sourceMapWriteTo' => $sourceMapTargetPath,
        ]);

        $sourceMapGenerator = new SourceMapGenerator($sourceMapOptions);
        $this->compiler->setSourceMap($sourceMapGenerator);

        $sass = $this->compiler->compile($content);

        $sourceMapPageView = BasePageView::createVirtual([
            'permalink' => $pageView->getPermalink() . '.map',
        ], $sourceMapGenerator->getSourceMapContents());

        $this->pageManager->trackNewPageView($sourceMapPageView);

        return $sass;
    }

    public function setOptions(array $options)
    {
        $this->options = $options;

        $this->configureImportPath();
        $this->configureOutputStyle();
        $this->configureSourceMap();
    }

    public function setPageManager(PageManager $pageManager)
    {
        $this->pageManager = $pageManager;
    }

    public function loadCache(Folder $cacheDir)
    {
        $cachePath = $cacheDir
            ->getFilesystemPath()
            ->appendToPath(self::CACHE_FILE)
        ;

        if (fs::exists($cachePath))
        {
            list(
                $this->compiler
            ) = unserialize(file_get_contents($cachePath));
        }
    }

    public function saveCache(Folder $cacheDir)
    {
        $cachePath = $cacheDir
            ->getFilesystemPath()
            ->appendToPath(self::CACHE_FILE)
        ;

        $cache = serialize([
            $this->compiler,
        ]);

        file_put_contents($cachePath, $cache);
    }

    private function configureImportPath()
    {
        $this->compiler->setImportPaths(Service::getWorkingDirectory() . '/_sass/');
    }

    private function configureOutputStyle()
    {
        $style = __::get($this->options, 'style', 'compressed');

        $this->compiler->setFormatter(self::stringToFormatter($style));
    }

    private function configureSourceMap()
    {
        $sourceMap = __::get($this->options, 'sourcemap');

        if ($sourceMap === 'inline')
        {
            $this->compiler->setSourceMap(Compiler::SOURCE_MAP_INLINE);
        }
        elseif ($sourceMap === true)
        {
            $this->compiler->setSourceMap(Compiler::SOURCE_MAP_FILE);
            $this->fileSourceMap = true;
        }
        else
        {
            $this->compiler->setSourceMap(Compiler::SOURCE_MAP_NONE);
        }
    }

    private function getSourceMapTargetFile(StaticPageView $pageView)
    {
        return fs::absolutePath(
            $this->configuration->getTargetFolder(),
            $pageView->getTargetFile() . '.map'
        );
    }

    private function handleThemeImports(&$content)
    {
        if (($themeName = $this->configuration->getTheme()))
        {
            $themePath = "../_themes/${themeName}/_sass";
            $content = preg_replace("/(@import ['\"])(@theme)(.+)/", "$1${themePath}$3", $content);
        }
    }

    public static function stringToFormatter($format)
    {
        if ($format == 'nested')
        {
            return Nested::class;
        }
        elseif ($format == 'expanded')
        {
            return Expanded::class;
        }
        elseif ($format == 'compact')
        {
            return Compact::class;
        }

        return Crunched::class;
    }
}
