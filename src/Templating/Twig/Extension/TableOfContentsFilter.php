<?php declare(strict_types=1);

/**
 * @copyright 2018 Vladimir Jimenez
 * @license   https://github.com/stakx-io/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Templating\Twig\Extension;

use allejo\stakx\Utilities\HtmlUtils;
use DOMDocument;
use DOMElement;
use Twig\TwigFilter;

class TableOfContentsFilter extends AbstractTwigExtension implements TwigFilterInterface
{
    /**
     * @param string      $html  The HTML we'll be parsing
     * @param null|string $id    The ID to assign to the generated TOC
     * @param null|string $class The class to assign to the generated TOC
     * @param int         $hMin  The heading minimum that'll be included
     * @param int         $hMax  The heading maximum that'll be included
     *
     * @see https://git.io/vdnEM Modified from @mattfarina's implementation
     */
    public function __invoke($html, $id = null, $class = null, $hMin = 1, $hMax = 6): string
    {
        if (!function_exists('simplexml_load_string')) {
            trigger_error('XML support is not available with the current PHP installation.', E_USER_WARNING);

            return '';
        }

        $dom = new DOMDocument();
        $headings = HtmlUtils::htmlXPath($dom, $html, '//h1|//h2|//h3|//h4|//h5|//h6');

        $toc = '';
        $curr = $last = 0;

        /** @var DOMElement $heading */
        foreach ($headings as $index => $heading) {
            if ($heading->attributes->getNamedItem('id') === null) {
                continue;
            }

            sscanf($heading->tagName, 'h%u', $curr);

            if (!($hMin <= $curr && $curr <= $hMax)) {
                continue;
            }

            $headingID = $heading->attributes->getNamedItem('id');

            if ($curr > $last) { // If the current level is greater than the last level indent one level
                $toc .= '<ul>';
            } elseif ($curr < $last) { // If the current level is less than the last level go up appropriate amount.
                $toc .= str_repeat('</li></ul>', $last - $curr) . '</li>';
            } else { // If the current level is equal to the last.
                $toc .= '</li>';
            }

            $toc .= '<li><a href="#' . $headingID->nodeValue . '">' . $heading->nodeValue . '</a>';
            $last = $curr;
        }

        $toc .= str_repeat('</li></ul>', $last - ($hMin - 1));

        if ($id !== null || $class !== null) {
            $attributes = [];

            if ($id !== null) {
                $attributes[] = sprintf('id="%s"', $id);
            }

            if ($class !== null) {
                $attributes[] = sprintf('class="%s"', $class);
            }

            $toc = substr_replace($toc, sprintf('<ul %s', implode(' ', $attributes)), 0, 3);
        }

        return $toc;
    }

    public static function get(): TwigFilter
    {
        return new TwigFilter('toc', new self());
    }
}
