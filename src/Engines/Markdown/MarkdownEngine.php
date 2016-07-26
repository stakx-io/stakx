<?php

namespace allejo\stakx\Engines;

use Highlight\Highlighter;

class MarkdownEngine extends \Parsedown
{
    protected $highlighter;

    public function __construct ()
    {
        $this->highlighter = new Highlighter();
    }

    public function blockFencedCodeComplete($block)
    {
        // The class has a `language-` prefix, remove this to get the language
        if (isset($block['element']['text']['attributes']))
        {
            $language = substr($block['element']['text']['attributes']['class'], 9);

            try
            {
                $highlighted = $this->highlighter->highlight($language, $block['element']['text']['text']);
                $block['element']['text']['text'] = $highlighted->value;
            }
            catch (\DomainException $exception) {}
        }

        return $block;
    }
}