<?php

/**
 * @copyright 2018 Vladimir Jimenez
 * @license   https://github.com/stakx-io/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Utilities;

abstract class HtmlUtils
{
    public static function htmlXPath(\DOMDocument &$DOMDocument, $html, $xpathQuery)
    {
        $html = self::normalizeHTML($html);

        libxml_use_internal_errors(true);

        $DOMDocument->loadHTML(
            mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'),
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
        );

        $xmlErrors = libxml_get_errors();

        /** @var \LibXMLError $error */
        foreach ($xmlErrors as $error)
        {
            // Ignore errors about invalid tags
            //   http://www.xmlsoft.org/html/libxml-xmlerror.html#xmlParserErrors
            if ($error->code === 801)
            {
                continue;
            }

            @trigger_error($error->message, E_USER_WARNING);
        }

        libxml_clear_errors();

        $xpath = new \DOMXPath($DOMDocument);

        return $xpath->query($xpathQuery);
    }

    private static function normalizeHTML($html)
    {
        if (strpos($html, '<body>') === false || strpos($html, '</body>') === false)
        {
            return sprintf('<body>%s</body>', $html);
        }

        return $html;
    }
}
