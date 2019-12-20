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
            $langDef = $this->parseInfoString($cssClass);

            try
            {
                $highlighted = $this->highlighter->highlight($langDef['language'], $block['element']['text']['text']);
                $value = $highlighted->value;

                if (count($langDef['selectedLines']) > 0)
                {
                    $lines = splitCodeIntoArray($value);
                    $value = '';

                    foreach ($lines as $i => $line)
                    {
                        // `$i + 1` since our line numbers are indexed starting at 1
                        $value .= vsprintf("<div class=\"loc%s\"><span>%s</span></div>\n", [
                            isset($langDef['selectedLines'][$i + 1]) ? ' highlighted' : '',
                            $line,
                        ]);
                    }
                }

                $block['markup'] = vsprintf('<pre><code class="hljs language-%s">%s</code></pre>', [
                    $langDef['language'],
                    $value,
                ]);

                // Only return the block if Highlighter knew the language and how to handle it.
                return $block;
            }
            // Exception thrown when language not supported
            catch (\DomainException $exception)
            {
                trigger_error("An unsupported language (${langDef['language']}) was detected in a code block", E_USER_WARNING);
            }
            catch (\Exception $e)
            {
                trigger_error('An error has occurred in the highlight.php language definition files', E_USER_WARNING);
            }
        }

        return parent::blockFencedCodeComplete($block);
    }

    private function parseInfoString($infoString)
    {
        $infoString = substr($infoString, 9);
        $definition = [
            'language' => $infoString,
            'selectedLines' => [],
        ];

        $bracePos = strpos($infoString, '{');

        if ($bracePos === false)
        {
            return $definition;
        }

        $definition['language'] = substr($infoString, 0, $bracePos);
        $lineDefinition = substr($infoString, $bracePos + 1, -1);
        $lineNumbers = explode(',', $lineDefinition);

        foreach ($lineNumbers as $lineNumber)
        {
            if (strpos($lineNumber, '-') === false)
            {
                $definition['selectedLines'][intval($lineNumber)] = true;
                continue;
            }

            $extremes = explode('-', $lineNumber);

            if (count($extremes) !== 2)
            {
                continue;
            }

            $start = intval($extremes[0]);
            $end = intval($extremes[1]);

            for ($i = $start; $i <= $end; $i++)
            {
                $definition['selectedLines'][$i] = true;
            }
        }

        return $definition;
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
