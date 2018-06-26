<?php

/**
 * @copyright 2018 Vladimir Jimenez
 * @license   https://github.com/stakx-io/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Markup;

use Gregwar\RST\Directives\CodeBlock;
use Gregwar\RST\HTML\Nodes\CodeNode;
use Gregwar\RST\Parser;
use Highlight\Highlighter;

class RstSyntaxBlock extends CodeBlock
{
    public function process(Parser $parser, $node, $variable, $data, array $options)
    {
        /** @var CodeNode $node */

        parent::process($parser, $node, $variable, $data, $options);

        try
        {
            $highlighter = new Highlighter();
            $highlighted = $highlighter->highlight($node->getLanguage(), $node->getValue());

            $nodeOutput = sprintf('<pre><code class="hljs language-%s">%s</code></pre>', $node->getLanguage(), $highlighted->value);

            $node->setRaw(true);
            $node->setValue($nodeOutput);
        }
        catch (\Exception $e)
        {
        }
    }
}
