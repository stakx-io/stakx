<?php

/**
 * @copyright 2018 Vladimir Jimenez
 * @license   https://github.com/allejo/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Templating\Twig\Extension;

use allejo\stakx\Utilities\HtmlUtils;

class SummaryFilter extends AbstractTwigExtension implements TwigFilterInterface
{
    public function __invoke($value, $paragraphCount = 1)
    {
        if (!extension_loaded('dom'))
        {
            @trigger_error('The DOM Extension is not loaded and is necessary for the "summary" Twig filter.', E_WARNING);
            return $value;
        }

        $dom = new \DOMDocument();
        $paragraphs = HtmlUtils::htmlXPath($dom, $value, sprintf('//body/p[position() <= %d]', $paragraphCount));

        $summary = '';

        foreach ($paragraphs as $paragraph)
        {
            $summary .= $dom->saveHTML($paragraph);
        }

        return $summary;
    }

    public static function get()
    {
        return new \Twig_SimpleFilter('summary', new self());
    }
}
