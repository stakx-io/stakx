<?php

/**
 * @copyright 2018 Vladimir Jimenez
 * @license   https://github.com/stakx-io/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Templating\Twig\MarkupBlock;

use Twig_Token;

/**
 * @author Gunnar Lium <gunnar@aptoma.com>
 * @author Joris Berthelot <joris@berthelot.tel>
 *
 * @see https://github.com/aptoma/twig-markdown/blob/master/src/Aptoma/Twig/TokenParser/MarkdownTokenParser.php
 */
class TokenParser extends \Twig_TokenParser
{
    private $tagName;

    public function __construct($tagName)
    {
        $this->tagName = $tagName;
    }

    /**
     * {@inheritdoc}
     */
    public function parse(Twig_Token $token)
    {
        $lineNumber = $token->getLine();

        $this->parser->getStream()->expect(\Twig_Token::BLOCK_END_TYPE);
        $body = $this->parser->subparse([$this, 'decideEndTag'], true);
        $this->parser->getStream()->expect(\Twig_Token::BLOCK_END_TYPE);

        return new Node($body, $lineNumber, $this->getTag());
    }

    /**
     * Decide if current token marks end of our markup block.
     *
     * @param Twig_Token $token
     *
     * @return bool
     */
    public function decideEndTag(\Twig_Token $token)
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
