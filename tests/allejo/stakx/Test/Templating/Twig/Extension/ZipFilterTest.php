<?php

/**
 * @copyright 2017 Vladimir Jimenez
 * @license   https://github.com/allejo/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Test\Templating\Twig\Extension;

use allejo\stakx\Test\PHPUnit_Stakx_TestCase;
use allejo\stakx\Templating\Twig\Extension\ZipFilter;

class ZipFilterTest extends PHPUnit_Stakx_TestCase
{
    public static function dataProviderZipData ()
    {
        return array(
            array(
                array('one', 'two', 'three'),
                array('1', '2', '3'),
                ' ',
                true,
                array('one 1', 'two 2', 'three 3'),
            ),
            array(
                array('four', 'five', 'six'),
                array('4', '5', '6'),
                '=',
                false,
                array('four=4', 'five=5', 'six=6'),
            ),
            array(
                array('hello', 'world'),
                array('4', '5', '6'),
                '=',
                true,
                array('hello=4', 'world=5'),
            ),
            array(
                array('hello', 'world'),
                array('4', '5', '6'),
                '=',
                false,
                array('hello=4', 'world=5', '6'),
            ),
            array(
                array('hello', 'world', "it's", 'nice', 'to', 'meet', 'you'),
                array('4', '5', '6'),
                ' ',
                true,
                array('hello 4', 'world 5', "it's 6"),
            ),
            array(
                array('hello', 'world', "it's", 'nice', 'to', 'meet', 'you'),
                array('4', '5', '6'),
                ' ',
                false,
                array('hello 4', 'world 5', "it's 6", 'nice', 'to', 'meet', 'you'),
            ),
            array(
                array('hello'),
                array('4', '5', '6'),
                ' ',
                true,
                array('hello 4'),
            ),
            array(
                array('hello'),
                array('4', '5', '6'),
                ' ',
                false,
                array('hello 4', '5', '6'),
            ),
            array(
                array('one', 'five', 'six'),
                array(null, '5', '6'),
                ' ',
                false,
                array('one', 'five 5', 'six 6'),
            ),
            array(
                array('one', 'five', 'six'),
                array('1', null, '6'),
                '=',
                false,
                array('one=1', 'five', 'six=6'),
            ),
        );
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
    public function testZipFilter (array $a1, array $a2, $glue, $strict, $expected)
    {
        $filter = new ZipFilter();
        $result = $filter($a1, $a2, $glue, $strict);

        $this->assertEquals($expected, $result);
    }
}
