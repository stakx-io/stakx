<?php declare(strict_types=1);

/**
 * @copyright 2018 Vladimir Jimenez
 * @license   https://github.com/stakx-io/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Test\Templating\Twig\Extension;

use allejo\stakx\MarkupEngine\MarkdownEngine;
use allejo\stakx\Templating\Twig\Extension\TableOfContentsFilter;
use allejo\stakx\Test\StakxTestCase;
use DOMDocument;

/**
 * @internal
 *
 * @coversNothing
 */
class TableOfContentsFilterTest extends StakxTestCase
{
    public static function provideTableOfContentsFilterCases(): iterable
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
     * @dataProvider provideTableOfContentsFilterCases
     */
    public function testTableOfContentsFilter(mixed $markdown, mixed $expectedHTML, mixed $id, mixed $class, mixed $hMin, mixed $hMax): void
    {
        $md = new MarkdownEngine($this->getMockAssetManager());
        $html = $md->parse($markdown);

        $filter = new TableOfContentsFilter();
        $toc = $filter($html, $id, $class, $hMin, $hMax);

        $rDOM = new DOMDocument();
        $rDOM->loadHTML($toc);
        $result = $this->standardizeHTML($rDOM->saveHTML());

        $eDOM = new DOMDocument();
        $eDOM->loadHTML($expectedHTML);
        $expected = $this->standardizeHTML($eDOM->saveHTML());

        $this->assertEquals($result, $expected);
    }

    /**
     * Remove indentation and new lines from HTML.
     *
     * @param string $html
     */
    private function standardizeHTML($html): string
    {
        return trim(preg_replace('/(\R\s+|\R)/', '', $html));
    }
}
