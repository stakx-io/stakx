<?php

/**
 * @copyright 2018 Vladimir Jimenez
 * @license   https://github.com/stakx-io/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\MarkupEngine;

use __;
use allejo\stakx\RuntimeStatus;
use allejo\stakx\Service;
use Highlight\Highlighter;
use function HighlightUtilities\splitCodeIntoArray;

class MarkdownEngine extends \ParsedownExtra implements MarkupEngineInterface
{
    use SyntaxHighlighterTrait;

    public function __construct()
    {
        parent::__construct();

        $this->highlighter = new Highlighter();
    }

    protected function blockHeader($Line)
    {
        $Block = parent::blockHeader($Line);

        if (isset($Block['element']['text']))
        {
            $Block['element']['attributes']['id'] = $this->slugifyHeader($Block);
        }

        return $Block;
    }

    protected function blockSetextHeader($Line, array $Block = null)
    {
        // @TODO Remove this `@` operator in an update to Parsedown and ParsedownExtra
        //   https://wiki.php.net/rfc/notice-for-non-valid-array-container
        //   https://github.com/erusev/parsedown-extra/issues/134
        $Block = @parent::blockSetextHeader($Line, $Block);

        if (isset($Block['element']['name']))
        {
            $element = $Block['element']['name'];

            if (in_array($element, ['h1', 'h2', 'h3', 'h4', 'h5', 'h6']))
            {
                $Block['element']['attributes']['id'] = $this->slugifyHeader($Block);
            }
        }

        return $Block;
    }

    private function slugifyHeader($Block)
    {
        return __::slug($Block['element']['text']);
    }

    protected function blockFencedCodeComplete($block)
    {
        // The class has a `language-` prefix, remove this to get the language
        if (isset($block['element']['text']['attributes']) && Service::hasRunTimeFlag(RuntimeStatus::USING_HIGHLIGHTER))
        {
            $cssClass = $block['element']['text']['attributes']['class'];
            $block['markup'] = $this->highlightCode($cssClass, $block['element']['text']['text']);

            return $block;
        }

        return parent::blockFencedCodeComplete($block);
    }

    ///
    // MarkupEngine Implementation
    ///

    /**
     * {@inheritdoc}
     */
    public function getTemplateTag()
    {
        return 'markdown';
    }

    /**
     * {@inheritdoc}
     */
    public function getExtensions()
    {
        return [
            'md',
            'mdown',
            'markdown',
        ];
    }
}
