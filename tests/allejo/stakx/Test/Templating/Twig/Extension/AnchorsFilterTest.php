<?php

/**
 * @copyright 2018 Vladimir Jimenez
 * @license   https://github.com/stakx-io/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Test\Templating\Twig\Extension;

use allejo\stakx\Templating\Twig\Extension\AnchorsFilter;
use allejo\stakx\Test\PHPUnit_Stakx_TestCase;

class AnchorsFilterTest extends PHPUnit_Stakx_TestCase
{
    public static function dataProviderAnchors()
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
        ];
    }

    /**
     * @dataProvider dataProviderAnchors
     *
     * @param $input
     * @param $expected
     * @param $options
     *
     * @throws \ReflectionException
     */
    public function testAnchorsFilter($input, $expected, $options)
    {
        $options['html'] = $input;

        $actual = trim($this->invokeClassFunctionWithNamedParams(AnchorsFilter::class, 'filter', $options));
        $expected = trim($expected);

        $this->assertEquals($expected, $actual);
    }
}
