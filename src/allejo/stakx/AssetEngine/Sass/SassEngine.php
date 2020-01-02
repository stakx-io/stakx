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
use allejo\stakx\Filesystem\WritableFolder;
use allejo\stakx\Manager\PageManager;
use allejo\stakx\Service;
use ScssPhp\ScssPhp\Compiler;
use ScssPhp\ScssPhp\Formatter\Compact;
use ScssPhp\ScssPhp\Formatter\Crunched;
use ScssPhp\ScssPhp\Formatter\Expanded;
use ScssPhp\ScssPhp\Formatter\Nested;

class SassEngine implements AssetEngineInterface
{
    /** @var bool */
    private $fileSourceMap = false;

    /** @var WritableFolder|null */
    private $cacheDirectory;

    /** @var Configuration */
    private $configuration;

    /** @var PageManager */
    private $pageManager;

    /** @var Compiler|null */
    private $compiler;

    /** @var array<string, mixed> */
    private $options = [];

    public function __construct(Configuration $configuration)
    {
        $this->configuration = $configuration;
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
        $this->initializeCompiler();

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
    }

    public function setPageManager(PageManager $pageManager)
    {
        $this->pageManager = $pageManager;
    }

    public function loadCache(WritableFolder $cacheDir)
    {
        $this->cacheDirectory = $cacheDir;
    }

    public function saveCache(WritableFolder $cacheDir)
    {
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

    private function initializeCompiler()
    {
        if ($this->compiler)
        {
            return;
        }

        $cacheOptions = [];

        // If we have a cache directory set, use it.
        if ($this->cacheDirectory)
        {
            $cacheOptions = [
                'cacheDir' => (string) $this->cacheDirectory->getFilesystemPath(),
                'forceRefresh' => false,
            ];
        }

        $this->compiler = new Compiler($cacheOptions);

        $this->configureImportPath();
        $this->configureOutputStyle();
        $this->configureSourceMap();
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
