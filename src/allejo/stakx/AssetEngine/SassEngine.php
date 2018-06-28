<?php

/**
 * @copyright 2018 Vladimir Jimenez
 * @license   https://github.com/allejo/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\AssetEngine;

use __;
use allejo\stakx\Configuration;
use allejo\stakx\Document\BasePageView;
use allejo\stakx\Document\StaticPageView;
use allejo\stakx\Filesystem\File;
use allejo\stakx\Filesystem\FilesystemLoader as fs;
use allejo\stakx\Manager\PageManager;
use allejo\stakx\Service;
use Leafo\ScssPhp\Compiler;
use Leafo\ScssPhp\Formatter\Compact;
use Leafo\ScssPhp\Formatter\Crunched;
use Leafo\ScssPhp\Formatter\Expanded;
use Leafo\ScssPhp\Formatter\Nested;

class SassEngine implements AssetEngineInterface
{
    private $fileSourceMap = false;
    private $configuration;
    private $pageManager;
    private $compiler;
    private $options = [];

    public function __construct(Configuration $configuration)
    {
        $this->configuration = $configuration;
        $this->compiler = new Compiler();
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
        $sourceMapTargetPath = null;
        $sourceMapOptions = [
            'sourceMapBasepath' => Service::getWorkingDirectory(),
        ];

        if ($this->fileSourceMap)
        {
            /** @var StaticPageView $pageView */
            $pageView = $options['pageview'];

            // Always put our source map next to the respective CSS file
            $sourceMapTargetPath = fs::appendPath(
                Service::getWorkingDirectory(),
                $this->configuration->getTargetFolder(),
                $pageView->getTargetFile() . '.map'
            );
            $sourceMapFilename = fs::getFilename($pageView->getTargetFile()) . '.map';

            $sourceMapOptions = array_merge($sourceMapOptions, [
                'sourceMapFilename' => $sourceMapFilename,
                'sourceMapURL' => $pageView->getPermalink() . '.map',
                'sourceMapWriteTo' => $sourceMapTargetPath,
            ]);
        }

        $this->compiler->setSourceMapOptions($sourceMapOptions);
        $sass = $this->compiler->compile($content);

        // Due to how our Sass Compiler is designed, the Source Map is automatically written to the given location. This
        // write happens *before* the stakx compiler writes out files, so if we write the source map to _site/, then it'll
        // be deleted when _site/ is cleaned.
        //
        // This is a workaround by creating a virtual file to store the source map contents, which will be written out by
        // the stakx compiler.
        if ($this->fileSourceMap)
        {
            $sourceMap = new File($sourceMapTargetPath);
            $sourceMapContents = $sourceMap->getContents();

            $sourceMapPageView = BasePageView::createVirtual([
                'permalink' => $pageView->getPermalink() . '.map',
            ], $sourceMapContents);

            $this->pageManager->trackNewPageView($sourceMapPageView);
        }

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

        if ($sourceMap === 'inline') {
            $this->compiler->setSourceMap(Compiler::SOURCE_MAP_INLINE);
        }
        elseif ($sourceMap === true) {
            $this->compiler->setSourceMap(Compiler::SOURCE_MAP_FILE);
            $this->fileSourceMap = true;
        }
        else {
            $this->compiler->setSourceMap(Compiler::SOURCE_MAP_NONE);
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
