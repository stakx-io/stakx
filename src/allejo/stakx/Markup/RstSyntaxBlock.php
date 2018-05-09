<?php

/**
 * @copyright 2018 Vladimir Jimenez
 * @license   https://github.com/allejo/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Markup;

use Gregwar\RST\Directives\CodeBlock;
use Gregwar\RST\Parser;
use Highlight\Highlighter;

class RstSyntaxBlock extends CodeBlock
{
    public function process(Parser $parser, $node, $variable, $data, array $options)
    {
        parent::process($parser, $node, $variable, $data, $options);

        $highlighter = new Highlighter();
        $highlighted = $highlighter->highlight($node->getLanguage(), $node->getValue());

        $node->setValue($highlighted->value);
    }
}
