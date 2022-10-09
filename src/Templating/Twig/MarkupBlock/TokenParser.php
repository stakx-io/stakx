<?php declare(strict_types=1);

/**
 * @copyright 2018 Vladimir Jimenez
 * @license   https://github.com/stakx-io/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Templating\Twig\MarkupBlock;

use Twig\Token;
use Twig\TokenParser\AbstractTokenParser;

/**
 * @author Gunnar Lium <gunnar@aptoma.com>
 * @author Joris Berthelot <joris@berthelot.tel>
 *
 * @see https://github.com/aptoma/twig-markdown/blob/master/src/Aptoma/Twig/TokenParser/MarkdownTokenParser.php
 */
class TokenParser extends AbstractTokenParser
{
    public function __construct(private $tagName)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function parse(Token $token)
    {
        $lineNumber = $token->getLine();

        $this->parser->getStream()->expect(Token::BLOCK_END_TYPE);
        $body = $this->parser->subparse($this->decideEndTag(...), true);
        $this->parser->getStream()->expect(Token::BLOCK_END_TYPE);

        return new Node($body, $lineNumber, $this->getTag());
    }

    /**
     * Decide if current token marks end of our markup block.
     */
    public function decideEndTag(Token $token): bool
    {
        return $token->test('end' . $this->getTag());
    }

    /**
     * {@inheritdoc}
     */
    public function getTag()
    {
        return $this->tagName;
    }
}
