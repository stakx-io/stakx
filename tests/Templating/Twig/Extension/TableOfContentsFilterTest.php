<?php

/**
 * @copyright 2018 Vladimir Jimenez
 * @license   https://github.com/stakx-io/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\test\Templating\Twig\Extension;

use allejo\stakx\MarkupEngine\MarkdownEngine;
use allejo\stakx\Templating\Twig\Extension\TableOfContentsFilter;
use allejo\stakx\Test\PHPUnit_Stakx_TestCase;

class TableOfContentsFilterTest extends PHPUnit_Stakx_TestCase
{
    public static function dataProviderTOC()
    {
        return [
            ['
# Heading 1

## Sub heading

# Heading 2
                ', '
<ul>
    <li>
        <a href="#heading-1">Heading 1</a>
        <ul>
            <li><a href="#sub-heading">Sub heading</a></li>
        </ul>
    </li>
    <li>
        <a href="#heading-2">Heading 2</a>
    </li>
</ul>',
                null, null, 1, 6,
            ],
            ['
# Heading 1

## Sub heading 1

# Heading 2

## Sub heading 2
                ', '
<ul>
    <li>
        <a href="#heading-1">Heading 1</a>
        <ul>
            <li><a href="#sub-heading-1">Sub heading 1</a></li>
        </ul>
    </li>
    <li>
        <a href="#heading-2">Heading 2</a>
        <ul>
            <li><a href="#sub-heading-2">Sub heading 2</a></li>
        </ul>
    </li>
</ul>',
                null, null, 1, 6,
            ],
            ['
# Heading 1

## Sub heading 1

# Heading 2

## Sub heading 2
                ', '
<ul id="my-toc-id">
    <li>
        <a href="#heading-1">Heading 1</a>
        <ul>
            <li><a href="#sub-heading-1">Sub heading 1</a></li>
        </ul>
    </li>
    <li>
        <a href="#heading-2">Heading 2</a>
        <ul>
            <li><a href="#sub-heading-2">Sub heading 2</a></li>
        </ul>
    </li>
</ul>',
                'my-toc-id', null, 1, 6,
            ],
            ['
# Heading 1

## Sub heading 1

# Heading 2

## Sub heading 2
                ', '
<ul>
    <li>
        <a href="#heading-1">Heading 1</a>
    </li>
    <li>
        <a href="#heading-2">Heading 2</a>
    </li>
</ul>',
                null, null, 1, 1,
            ],
            ['
# Heading 1

## Sub heading 1

## Sub heading 2

## Sub heading 3
                ', '
<ul>
    <li>
        <a href="#sub-heading-1">Sub heading 1</a>
    </li>
    <li>
        <a href="#sub-heading-2">Sub heading 2</a>
    </li>
    <li>
        <a href="#sub-heading-3">Sub heading 3</a>
    </li>
</ul>',
                null, null, 2, 6,
            ],
            ['
# Heading 1
                ', '
<ul class="toc-class">
    <li>
        <a href="#heading-1">Heading 1</a>
    </li>
</ul>',
                null, 'toc-class', 1, 6,
            ],
            ['
# Heading 1
                ', '
<ul id="my-toc" class="toc-class">
    <li>
        <a href="#heading-1">Heading 1</a>
    </li>
</ul>',
                'my-toc', 'toc-class', 1, 6,
            ],
        ];
    }

    /**
     * @dataProvider dataProviderTOC
     *
     * @param mixed $markdown
     * @param mixed $expectedHTML
     * @param mixed $id
     * @param mixed $class
     * @param mixed $hMin
     * @param mixed $hMax
     */
    public function testTableOfContentsFilter($markdown, $expectedHTML, $id, $class, $hMin, $hMax)
    {
        $md = new MarkdownEngine($this->getMockAssetManager());
        $html = $md->parse($markdown);

        $filter = new TableOfContentsFilter();
        $toc = $filter($html, $id, $class, $hMin, $hMax);

        $rDOM = new \DOMDocument();
        $rDOM->loadHTML($toc);
        $result = $this->standardizeHTML($rDOM->saveHTML());

        $eDOM = new \DOMDocument();
        $eDOM->loadHTML($expectedHTML);
        $expected = $this->standardizeHTML($eDOM->saveHTML());

        $this->assertEquals($result, $expected);
    }

    /**
     * Remove indentation and new lines from HTML.
     *
     * @param string $html
     *
     * @return string
     */
    private function standardizeHTML($html)
    {
        return trim(preg_replace('/(\R\s+|\R)/', '', $html));
    }
}
