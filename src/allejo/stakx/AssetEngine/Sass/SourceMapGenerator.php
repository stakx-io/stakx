<?php

/**
 * @copyright 2018 Vladimir Jimenez
 * @license   https://github.com/stakx-io/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\AssetEngine\Sass;

use ScssPhp\ScssPhp\SourceMap\SourceMapGenerator as BaseGenerator;

/**
 * A custom SourceMapGenerator to override the one provided by ScssPhp.
 *
 * This version stores the content of its source map and makes it available for a later time. This class allows us to
 * give the source map to our stakx Compiler to write out instead of letting ScssPhp do that.
 */
class SourceMapGenerator extends BaseGenerator
{
    protected $generatorOptions;
    protected $sourceMapContent;

    public function __construct(array $options = [])
    {
        parent::__construct($options);

        $this->generatorOptions = $options;
    }

    public function getSourceMapContents()
    {
        return $this->sourceMapContent;
    }

    public function saveMap($content)
    {
        $this->sourceMapContent = $content;

        return $this->generatorOptions['sourceMapURL'];
    }
}
