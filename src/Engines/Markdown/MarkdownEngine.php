<?php

namespace allejo\stakx\Engines;

use Highlight\Highlighter;

class MarkdownEngine extends \ParsedownExtra
{
    protected $highlighter;

    public function __construct ()
    {
        parent::__construct();

        $this->highlighter = new Highlighter();
    }

    protected function blockHeader($line)
    {
        $Block = parent::blockHeader($line);

        // Create our unique ids by sanitizing the header content
        $id = strtolower($Block['element']['text']);
        $id = str_replace(' ', '-', $id);
        $id = preg_replace('/[^0-9a-zA-Z-_]/', '', $id);
        $id = preg_replace('/-+/', '-', $id);

        $Block['element']['attributes']['id'] = $id;

        return $Block;
    }

    public function blockFencedCodeComplete ($block)
    {
        // The class has a `language-` prefix, remove this to get the language
        if (isset($block['element']['text']['attributes']))
        {
            $language = substr($block['element']['text']['attributes']['class'], 9);

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