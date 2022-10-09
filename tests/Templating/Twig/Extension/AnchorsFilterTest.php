<?php declare(strict_types=1);

/**
 * @copyright 2018 Vladimir Jimenez
 * @license   https://github.com/stakx-io/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Test\Templating\Twig\Extension;

use allejo\stakx\Templating\Twig\Extension\AnchorsFilter;
use allejo\stakx\Test\StakxTestCase;
use ReflectionException;

/**
 * @internal
 *
 * @coversNothing
 */
class AnchorsFilterTest extends StakxTestCase
{
    public static function provideAnchorsFilterCases(): iterable
    {
        return [
            [
                '
                    <h1 id="heading-1">Heading 1</h1>
                    <h2 id="heading-2">Heading 2</h2>
                ',
                '
                    <h1 id="heading-1">Heading 1 <a href="#heading-1"></a></h1>
                    <h2 id="heading-2">Heading 2 <a href="#heading-2"></a></h2>
                ',
                [],
            ],
            [
                '
                    <h1 id="heading-1">Heading 1</h1>
                    <h2 id="heading-2">Heading 2</h2>
                ',
                '
                    <h1 id="heading-1"><a href="#heading-1"></a> Heading 1</h1>
                    <h2 id="heading-2"><a href="#heading-2"></a> Heading 2</h2>
                ',
                [
                    'beforeHeading' => true,
                ],
            ],
            [
                '
                    <h1 id="heading-1">Heading 1</h1>
                    <h2 id="heading-2">Heading 2</h2>
                ',
                '
                    <h1 id="heading-1"><a href="#heading-1" class="heading-anchor">#</a> Heading 1</h1>
                    <h2 id="heading-2"><a href="#heading-2" class="heading-anchor">#</a> Heading 2</h2>
                ',
                [
                    'beforeHeading' => true,
                    'anchorBody' => '#',
                    'anchorClass' => 'heading-anchor',
                ],
            ],
            [
                '
                    <h1 id="heading-1">Heading 1</h1>
                    <h2 id="heading-2">Heading 2</h2>
                ',
                '
                    <h1 id="heading-1"><a href="#heading-1" class="heading-anchor"><i class="fa fa-link" aria-hidden="true"></i></a> Heading 1</h1>
                    <h2 id="heading-2"><a href="#heading-2" class="heading-anchor"><i class="fa fa-link" aria-hidden="true"></i></a> Heading 2</h2>
                ',
                [
                    'beforeHeading' => true,
                    'anchorBody' => '<i class="fa fa-link" aria-hidden="true"></i>',
                    'anchorClass' => 'heading-anchor',
                ],
            ],
            [
                '
                    <h1 id="heading-1">Heading 1</h1>
                    <h2 id="heading-2">Heading 2</h2>
                ',
                '
                    <h1 id="heading-1">Heading 1 <a href="#heading-1"><span><span class="sr-only">Link to Heading 1</span> <i class="fa fa-url" aria-hidden="true"></i></span></a></h1>
                    <h2 id="heading-2">Heading 2 <a href="#heading-2"><span><span class="sr-only">Link to Heading 2</span> <i class="fa fa-url" aria-hidden="true"></i></span></a></h2>
                ',
                [
                    'anchorBody' => '<span><span class="sr-only">Link to {heading}</span> <i class="fa fa-url" aria-hidden="true"></i></span>',
                ],
            ],
            [
                '
                    <h1 id="heading-1">Heading 1</h1>
                    <h2 id="heading-2">Heading 2</h2>
                ',
                '
                    <h1 id="heading-1">Heading 1 <a href="#heading-1">Link to Heading 1 &lt;i class="fa fa-url" aria-hidden="true"&gt;&lt;/i&gt;</a></h1>
                    <h2 id="heading-2">Heading 2 <a href="#heading-2">Link to Heading 2 &lt;i class="fa fa-url" aria-hidden="true"&gt;&lt;/i&gt;</a></h2>
                ',
                [
                    'anchorBody' => 'Link to {heading} <i class="fa fa-url" aria-hidden="true"></i>',
                ],
            ],
            [
                '
                    <h1 id="heading-1">Heading 1</h1>
                    <h2 id="heading-2">Heading 2</h2>
                ',
                '
                    <h1 id="heading-1">Heading 1 <a href="#heading-1">&raquo;</a></h1>
                    <h2 id="heading-2">Heading 2 <a href="#heading-2">&raquo;</a></h2>
                ',
                [
                    'anchorBody' => '&raquo;',
                ],
            ],
            [
                '
                    <h1 id="heading-1">Heading 1</h1>
                    <h2 id="heading-2">Heading 2</h2>
                ',
                '
                    <h1 id="heading-1">Heading 1 <a href="#heading-1" aria-hidden="true" data-toast="foo" title="Heading 1 hello"></a></h1>
                    <h2 id="heading-2">Heading 2 <a href="#heading-2" aria-hidden="true" data-toast="foo" title="Heading 2 hello"></a></h2>
                ',
                [
                    'anchorAttrs' => [
                        'aria-hidden' => 'true',
                        'data-toast' => 'foo',
                        'title' => 'Hello {heading}',
                    ],
                    'anchorTitle' => '{heading} hello',
                ],
            ],
            [
                '
                    <h1 id="heading-1">Heading 1</h1>
                    <h2 id="heading-2">Heading 2</h2>
                    <h3 id="heading-3">Heading 3</h3>
                    <h4 id="heading-4">Heading 4</h4>
                    <h5 id="heading-5">Heading 5</h5>
                ',
                '
                    <h1 id="heading-1">Heading 1</h1>
                    <h2 id="heading-2">Heading 2 <a href="#heading-2"></a></h2>
                    <h3 id="heading-3">Heading 3 <a href="#heading-3"></a></h3>
                    <h4 id="heading-4">Heading 4 <a href="#heading-4"></a></h4>
                    <h5 id="heading-5">Heading 5</h5>
                ',
                [
                    'hMin' => 2,
                    'hMax' => 4,
                ],
            ],
        ];
    }

    /**
     * @dataProvider provideAnchorsFilterCases
     *
     * @throws ReflectionException
     */
    public function testAnchorsFilter(mixed $input, mixed $expected, mixed $options): void
    {
        $options['html'] = $input;

        $actual = trim((string)$this->invokeClassFunctionWithNamedParams(AnchorsFilter::class, 'filter', $options));
        $expected = trim((string)$expected);

        $this->assertEquals($expected, $actual);
    }
}
