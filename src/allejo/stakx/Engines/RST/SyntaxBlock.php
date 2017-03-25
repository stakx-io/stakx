<?php

/**
 * @copyright 2017 Vladimir Jimenez
 * @license   https://github.com/allejo/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Engines\RST;

use Gregwar\RST\Directives\CodeBlock;
use Gregwar\RST\Parser;
use Highlight\Highlighter;

class SyntaxBlock extends CodeBlock
{
    public function process(Parser $parser, $node, $variable, $data, array $options)
    {
        parent::process($parser, $node, $variable, $data, $options);

        $highlighter = new Highlighter();
        $highlighted = $highlighter->highlight($node->getLanguage(), $node->getValue());

        $node->setValue($highlighted->value);
    }
}
