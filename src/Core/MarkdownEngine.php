<?php

namespace allejo\stakx\Core;

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
        $language = substr($block['element']['text']['attributes']['class'], 9);
        $highlighted = $this->highlighter->highlight($language, $block['element']['text']['text']);
        $block['element']['text']['text'] = $highlighted->value;

        return $block;
    }
}