<?php

/**
 * @copyright 2017 Vladimir Jimenez
 * @license   https://github.com/allejo/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Engines\Markdown;

use allejo\stakx\Configuration;
use allejo\stakx\Engines\ParsingEngine;
use allejo\stakx\Manager\HighlighterManager;
use allejo\stakx\Service;
use Highlight\Highlighter;

class MarkdownEngine extends \ParsedownExtra implements ParsingEngine
{
    protected $highlighter;

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
        $Block = parent::blockSetextHeader($Line, $Block);

        if (isset($Block['element']['name']))
        {
            $element = $Block['element']['name'];

            if ($element == 'h1' || $element == 'h2')
            {
                $Block['element']['attributes']['id'] = $this->slugifyHeader($Block);
            }
        }

        return $Block;
    }

    private function slugifyHeader($Block)
    {
        $id = strtolower($Block['element']['text']);
        $id = str_replace(' ', '-', $id);
        $id = preg_replace('/[^0-9a-zA-Z-_]/', '', $id);

        return preg_replace('/-+/', '-', $id);
    }

    protected function blockFencedCodeComplete($block)
    {
        // The class has a `language-` prefix, remove this to get the language
        if (isset($block['element']['text']['attributes']) && Service::getParameter(Configuration::HIGHLIGHTER_ENABLED))
        {
            $cssClass = &$block['element']['text']['attributes']['class'];
            $language = substr($cssClass, 9);
            $cssClass = 'hljs ' . $cssClass;

            try
            {
                $highlighted = $this->highlighter->highlight($language, $block['element']['text']['text']);
                $block['element']['text']['text'] = $highlighted->value;

                // Only return the block if Highlighter knew the language and how to handle it.
                return $block;
            }
            // Exception thrown when language not supported
            catch (\DomainException $exception)
            {
                trigger_error("An unsupported language ($language) was detected in a code block", E_USER_WARNING);
            }
        }

        return parent::blockFencedCodeComplete($block);
    }
}
