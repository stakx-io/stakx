<?php

/**
 * @copyright 2018 Vladimir Jimenez
 * @license   https://github.com/stakx-io/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Test\Templating\Twig\Extension;

use allejo\stakx\Templating\Twig\Extension\ZipFilter;
use allejo\stakx\Test\PHPUnit_Stakx_TestCase;

class ZipFilterTest extends PHPUnit_Stakx_TestCase
{
    public static function dataProviderZipData()
    {
        return [
            [
                ['one', 'two', 'three'],
                ['1', '2', '3'],
                ' ',
                true,
                ['one 1', 'two 2', 'three 3'],
            ],
            [
                ['four', 'five', 'six'],
                ['4', '5', '6'],
                '=',
                false,
                ['four=4', 'five=5', 'six=6'],
            ],
            [
                ['hello', 'world'],
                ['4', '5', '6'],
                '=',
                true,
                ['hello=4', 'world=5'],
            ],
            [
                ['hello', 'world'],
                ['4', '5', '6'],
                '=',
                false,
                ['hello=4', 'world=5', '6'],
            ],
            [
                ['hello', 'world', "it's", 'nice', 'to', 'meet', 'you'],
                ['4', '5', '6'],
                ' ',
                true,
                ['hello 4', 'world 5', "it's 6"],
            ],
            [
                ['hello', 'world', "it's", 'nice', 'to', 'meet', 'you'],
                ['4', '5', '6'],
                ' ',
                false,
                ['hello 4', 'world 5', "it's 6", 'nice', 'to', 'meet', 'you'],
            ],
            [
                ['hello'],
                ['4', '5', '6'],
                ' ',
                true,
                ['hello 4'],
            ],
            [
                ['hello'],
                ['4', '5', '6'],
                ' ',
                false,
                ['hello 4', '5', '6'],
            ],
            [
                ['one', 'five', 'six'],
                [null, '5', '6'],
                ' ',
                false,
                ['one', 'five 5', 'six 6'],
            ],
            [
                ['one', 'five', 'six'],
                ['1', null, '6'],
                '=',
                false,
                ['one=1', 'five', 'six=6'],
            ],
        ];
    }

    /**
     * @dataProvider dataProviderZipData
     *
     * @param array  $a1
     * @param array  $a2
     * @param string $glue
     * @param bool   $strict
     * @param array  $expected
     */
    public function testZipFilter(array $a1, array $a2, $glue, $strict, $expected)
    {
        $filter = new ZipFilter();
        $result = $filter($a1, $a2, $glue, $strict);

        $this->assertEquals($expected, $result);
    }
}
