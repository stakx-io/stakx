<?php declare(strict_types=1);

/**
 * @copyright 2018 Vladimir Jimenez
 * @license   https://github.com/stakx-io/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Markup;

use Doctrine\RST\Directives\CodeBlock;
use Doctrine\RST\Directives\Directive;
use Doctrine\RST\Parser;
use Highlight\Highlighter;

class RstSyntaxBlock extends Directive
{
    use SyntaxHighlighterTrait;

    private readonly CodeBlock $baseDirective;

    public function __construct()
    {
        $this->highlighter = new Highlighter();
        $this->baseDirective = new CodeBlock();
    }

    public function getName(): string
    {
        return 'code-block';
    }

    public function process(Parser $parser, $node, $variable, $data, array $options): void
    {
        $this->baseDirective->process($parser, $node, $variable, $data, $options);

        $nodeOutput = $this->highlightCode($node->getLanguage(), $node->getValue());

        $node->setRaw(true);
        $node->setValue($nodeOutput);
    }

    public function wantCode(): bool
    {
        return true;
    }
}
