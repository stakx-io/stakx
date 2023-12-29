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
use ScssPhp\ScssPhp\Exception\SassException;
use ScssPhp\ScssPhp\Formatter\Compact;
use ScssPhp\ScssPhp\Formatter\Compressed;
use ScssPhp\ScssPhp\Formatter\Crunched;
use ScssPhp\ScssPhp\Formatter\Expanded;
use ScssPhp\ScssPhp\Formatter\Nested;
use ScssPhp\ScssPhp\OutputStyle;

class SassEngine implements AssetEngineInterface
{
    private ?WritableFolder $cacheDirectory;
    private Configuration $configuration;
    private PageManager $pageManager;
    private ?Compiler $compiler;
    /** @var array<string, mixed> */
    private array $options = [];
    private bool $fileSourceMap = false;

    public function __construct(Configuration $configuration)
    {
        $this->configuration = $configuration;
    }

    public function getName(): string
    {
        return 'Sass';
    }

    public function getConfigurationNamespace(): string
    {
        return 'scss';
    }

    public function getDefaultConfiguration(): array
    {
        return [
            'style' => 'compressed',
            'sourcemap' => false,
        ];
    }

    public function getFolder(): string
    {
        return '_sass';
    }

    /**
     * @return string[]
     */
    public function getExtensions(): array
    {
        return ['scss'];
    }

    /**
     * @param array{pageview: StaticPageView} $options
     *
     * @throws SassException
     */
    public function parse(string $content, array $options = []): string
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

            return $this->compiler->compileString($content)->getCss();
        }

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

        $sass = $this->compiler->compileString($content)->getCss();

        $sourceMapPageView = BasePageView::createVirtual([
            'permalink' => $pageView->getPermalink() . '.map',
        ], $sourceMapGenerator->getSourceMapContents());

        $this->pageManager->trackNewPageView($sourceMapPageView);

        return $sass;
    }

    public function setOptions(array $options): void
    {
        $this->options = $options;
    }

    public function setPageManager(PageManager $pageManager): void
    {
        $this->pageManager = $pageManager;
    }

    public function loadCache(WritableFolder $cacheDir): void
    {
        $this->cacheDirectory = $cacheDir;
    }

    public function saveCache(WritableFolder $cacheDir): void
    {
    }

    private function configureImportPath(): void
    {
        $this->compiler->setImportPaths(Service::getWorkingDirectory() . '/_sass/');
    }

    private function configureOutputStyle(): void
    {
        $style = __::get($this->options, 'style', 'compressed');

        $this->compiler->setOutputStyle(self::stringToFormatter($style));
    }

    private function configureSourceMap(): void
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

    private function getSourceMapTargetFile(StaticPageView $pageView): string
    {
        return fs::absolutePath(
            $this->configuration->getTargetFolder(),
            $pageView->getTargetFile() . '.map'
        );
    }

    private function initializeCompiler(): void
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

    private function handleThemeImports(string &$content): void
    {
        if (($themeName = $this->configuration->getTheme()))
        {
            $themePath = "../_themes/{$themeName}/_sass";
            $content = preg_replace("/(@import ['\"])(@theme)(.+)/", "$1{$themePath}$3", $content);
        }
    }

    public static function stringToFormatter($format): string
    {
        return match ($format) {
            'nested', 'expanded' => OutputStyle::EXPANDED,

            // Also handle `compressed`
            default => OutputStyle::COMPRESSED,
        };
    }
}
