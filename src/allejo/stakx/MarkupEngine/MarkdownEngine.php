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

class MarkdownEngine extends \ParsedownExtra implements MarkupEngineInterface
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
            $language = substr($cssClass, 9);

            try
            {
                $highlighted = $this->highlighter->highlight($language, $block['element']['text']['text']);
                $block['markup'] = "<pre><code class=\"hljs ${cssClass}\">" . $highlighted->value . '</code></pre>';

                // Only return the block if Highlighter knew the language and how to handle it.
                return $block;
            }
            // Exception thrown when language not supported
            catch (\DomainException $exception)
            {
                trigger_error("An unsupported language ($language) was detected in a code block", E_USER_WARNING);
            }
            catch (\Exception $e)
            {
                trigger_error('An error has occurred in the highlight.php language definition files', E_USER_WARNING);
            }
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
