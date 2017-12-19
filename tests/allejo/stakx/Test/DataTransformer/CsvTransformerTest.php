<?php

/**
 * @copyright 2017 Vladimir Jimenez
 * @license   https://github.com/allejo/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Test\DataTransformer;

use allejo\stakx\DataTransformer\CsvTransformer;
use allejo\stakx\Test\PHPUnit_Stakx_TestCase;

class CsvTransformerTest extends PHPUnit_Stakx_TestCase
{
    public function testValidCsvData()
    {
        $file = <<<FILE
id,name,gender
1,John Doe,M
2,Jane Doe,F
FILE;

        $actual = CsvTransformer::transformData($file);
        $expected = [
            [
                'id' => 1,
                'name' => 'John Doe',
                'gender' => 'M',
            ],
            [
                'id' => 2,
                'name' => 'Jane Doe',
                'gender' => 'F',
            ],
        ];

        $this->assertEquals($expected, $actual);
    }

    public function testInvalidCsvData()
    {
        $file = <<<FILE
{ "message": "this is actually json" }
FILE;

        $actual = CsvTransformer::transformData($file);
        $expected = [];

        $this->assertEquals($expected, $actual);
    }
}
