<?php declare(strict_types=1);

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
    use SyntaxHighlighterTrait;

    public function __construct()
    {
        $this->highlighter = new Highlighter();
    }

    public function process(Parser $parser, $node, $variable, $data, array $options): void
    {
        // @var CodeNode $node

        parent::process($parser, $node, $variable, $data, $options);

        $nodeOutput = $this->highlightCode($node->getLanguage(), $node->getValue());

        $node->setRaw(true);
        $node->setValue($nodeOutput);
    }
}
