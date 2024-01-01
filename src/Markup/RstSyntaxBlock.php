<?php

/**
 * @copyright 2018 Vladimir Jimenez
 * @license   https://github.com/stakx-io/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Markup;

use Doctrine\RST\Directives\Directive;
use Doctrine\RST\Nodes\CodeNode;
use Doctrine\RST\Nodes\Node;
use Doctrine\RST\Parser;
use Highlight\Highlighter;

class RstSyntaxBlock extends Directive
{
    use SyntaxHighlighterTrait;

    public function __construct()
    {
        $this->highlighter = new Highlighter();
    }

    public function getName(): string
    {
        return 'code-block';
    }

    public function process(
        Parser $parser,
        ?Node  $node,
        string $variable,
        string $data,
        array  $options
    ): void {
        if ($node === null) {
            return;
        }

        if ($node instanceof CodeNode) {
            $node->setLanguage(trim($data));
            $node->setOptions($options);
        }

        if ($variable !== '') {
            $environment = $parser->getEnvironment();
            $environment->setVariable($variable, $node);
        } else {
            $document = $parser->getDocument();
            $document->addNode($node);
        }

        $nodeOutput = $this->highlightCode($node->getLanguage(), $node->getValue());

        $node->setRaw(true);
        $node->setValue($nodeOutput);
    }

    public function wantCode(): bool
    {
        return true;
    }
}
