<?php

/**
 * @copyright 2018 Vladimir Jimenez
 * @license   https://github.com/stakx-io/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\MarkupEngine;

use allejo\stakx\RuntimeStatus;
use allejo\stakx\Service;
use Highlight\Highlighter;
use function HighlightUtilities\splitCodeIntoArray;

trait SyntaxHighlighterTrait
{
    /** @var Highlighter */
    protected $highlighter;

    /**
     * @param string $infoString
     * @param string $rawCode
     *
     * @return string
     */
    protected function highlightCode($infoString, $rawCode)
    {
        $langDef = $this->parseInfoString($infoString);

        try
        {
            $highlighted = $this->highlighter->highlight($langDef['language'], $rawCode);
            $value = $highlighted->value;

            if (Service::hasRunTimeFlag(RuntimeStatus::USING_LINE_NUMBERS) || count($langDef['selectedLines']) > 0)
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

            return vsprintf('<pre><code class="hljs language-%s">%s</code></pre>', [
                $langDef['language'],
                $value,
            ]);
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

        return $rawCode;
    }

    /**
     * @param string $infoString
     *
     * @return array
     */
    private function parseInfoString($infoString)
    {
        $infoString = str_replace('language-', '', $infoString);
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

            for ($i = $start; $i <= $end; ++$i)
            {
                $definition['selectedLines'][$i] = true;
            }
        }

        return $definition;
    }
}
