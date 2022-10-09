<?php declare(strict_types=1);

/**
 * @copyright 2018 Vladimir Jimenez
 * @license   https://github.com/stakx-io/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Test\Utilities;

use allejo\stakx\Utilities\HtmlUtils;
use DOMDocument;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
class HtmlUtilsTest extends TestCase
{
    public function testRenderedSmartQuotesRemain(): void
    {
        $content = <<<'HTML'
<p>
Lorem ipsum dolor sit &ldquo;amet&rdquo;, consectetur adipisicing elit.
&lsquo;Blanditiis&rsquo; cupiditate debitis dignissimos.
</p>
HTML;
        $utf8_rendered = html_entity_decode($content);

        $dom = new DOMDocument();
        $paragraphs = HtmlUtils::htmlXPath($dom, $utf8_rendered, '//body/p[position() <= 1]');
        $output = '';

        foreach ($paragraphs as $paragraph) {
            $output .= $dom->saveHTML($paragraph);
        }

        $this->assertEquals($utf8_rendered, $output);
    }
}
