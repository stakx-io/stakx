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

class AnchorsFilter extends AbstractTwigExtension implements TwigFilterInterface
{
    /**
     * @param string $html          The HTML we'll be processing
     * @param bool   $beforeHeading Set to true if the anchor should be placed before the heading's content
     * @param array  $anchorAttrs   Any custom HTML attributes that will be added to the `<a>` tag; you may NOT use
     *                              `href`, `class`, or `title`
     * @param string $anchorBody    The content that will be placed inside the anchor; the `{heading}` placeholder is
     *                              available
     * @param string $anchorClass   The class(es) that will be used for each anchor. Separate multiple classes with a
     *                              space
     * @param string $anchorTitle   The title attribute that will be used for anchors; the `{heading}` placeholder is
     *                              available
     * @param int    $hMin          The minimum header level to build an anchor for; any header lower than this value
     *                              will be ignored
     * @param int    $hMax          The maximum header level to build an anchor for; any header greater than this value
     *                              will be ignored
     */
    public static function filter($html, $beforeHeading = false, $anchorAttrs = [], $anchorBody = '', $anchorClass = '', $anchorTitle = '', $hMin = 1, $hMax = 6): string
    {
        if (!function_exists('simplexml_load_string')) {
            trigger_error('XML support is not available with the current PHP installation.', E_USER_WARNING);

            return $html;
        }

        if ($anchorClass) {
            $anchorAttrs['class'] = $anchorClass;
        }

        $dom = new DOMDocument();
        $currLvl = 0;
        $headings = HtmlUtils::htmlXPath($dom, $html, '//h1|//h2|//h3|//h4|//h5|//h6');

        /** @var DOMElement $heading */
        foreach ($headings as $heading) {
            $headingID = $heading->attributes->getNamedItem('id');

            if ($headingID === null) {
                continue;
            }

            sscanf($heading->tagName, 'h%u', $currLvl);

            if (!($hMin <= $currLvl && $currLvl <= $hMax)) {
                continue;
            }

            $anchor = $dom->createElement('a');
            $anchor->setAttribute('href', '#' . $headingID->nodeValue);

            $body = strtr($anchorBody, [
                '{heading}' => $heading->textContent,
            ]);

            if (str_starts_with($body, '<')) {
                $domAnchorBody = new DOMDocument();
                $loaded = @$domAnchorBody->loadHTML($body, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);

                if ($loaded) {
                    /** @var DOMElement $childNode */
                    foreach ($domAnchorBody->childNodes as $childNode) {
                        $node = $anchor->ownerDocument->importNode($childNode->cloneNode(true), true);
                        $anchor->appendChild($node);
                    }
                }
            } else {
                $anchor->nodeValue = $body;
            }

            if ($anchorTitle) {
                $anchorAttrs['title'] = strtr($anchorTitle, [
                    '{heading}' => $heading->textContent,
                ]);
            }

            foreach ($anchorAttrs as $attrName => $attrValue) {
                $anchor->setAttribute($attrName, $attrValue);
            }

            if ($beforeHeading) {
                $heading->insertBefore($dom->createTextNode(' '), $heading->childNodes[0]);
                $heading->insertBefore($anchor, $heading->childNodes[0]);
            } else {
                $heading->appendChild($dom->createTextNode(' '));
                $heading->appendChild($anchor);
            }
        }

        return preg_replace('/<\\/?body>/', '', $dom->saveHTML());
    }

    /**
     * {@inheritdoc}
     */
    public static function get(): TwigFilter
    {
        return new TwigFilter('anchors', self::class . '::filter');
    }
}
