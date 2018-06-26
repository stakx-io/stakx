<?php

/**
 * @copyright 2018 Vladimir Jimenez
 * @license   https://github.com/allejo/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\AssetEngine;

use __;
use allejo\stakx\Service;
use Leafo\ScssPhp\Compiler;
use Leafo\ScssPhp\Formatter\Compact;
use Leafo\ScssPhp\Formatter\Crunched;
use Leafo\ScssPhp\Formatter\Expanded;
use Leafo\ScssPhp\Formatter\Nested;

class SassEngine implements AssetEngine
{
    private $compiler;
    private $options;

    public function __construct(array $options = [])
    {
        $this->compiler = new Compiler();
        $this->options = $options;

        $this->configureImportPath();
        $this->configureOutputStyle();
        $this->configureSourceMap();
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

    public function parse($content)
    {
        return $this->compiler->compile($content);
    }

    private function configureImportPath()
    {
        $this->compiler->setImportPaths(Service::getWorkingDirectory() . '/_sass/');
    }

    private function configureOutputStyle()
    {
        $style = __::get($this->options, 'style', 'compressed');

        switch ($style)
        {
            case 'nested':
                $this->compiler->setFormatter(Nested::class);
                break;

            case 'expanded':
                $this->compiler->setFormatter(Expanded::class);
                break;

            case 'compact':
                $this->compiler->setFormatter(Compact::class);
                break;

            case 'compressed':
            default:
                $this->compiler->setFormatter(Crunched::class);
        }
    }

    private function configureSourceMap()
    {
        $sourcemap = __::get($this->options, 'sourcemap');

        if ($sourcemap === false || $sourcemap === null) {
            $this->compiler->setSourceMap(Compiler::SOURCE_MAP_NONE);
        }
        elseif ($sourcemap === 'inline') {
            $this->compiler->setSourceMap(Compiler::SOURCE_MAP_INLINE);
        }
        else {
            $this->compiler->setSourceMap(Compiler::SOURCE_MAP_FILE);
            $this->compiler->setSourceMapOptions([
                'sourceMapWriteTo' => $sourcemap
            ]);
        }
    }
}
