<?php declare(strict_types=1);

/**
 * @copyright 2018 Vladimir Jimenez
 * @license   https://github.com/stakx-io/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Test\DataTransformer;

use allejo\stakx\DataTransformer\CsvTransformer;
use allejo\stakx\Test\StakxTestCase;

/**
 * @internal
 *
 * @coversNothing
 */
class CsvTransformerTest extends StakxTestCase
{
    public function testValidCsvData(): void
    {
        $file = <<<'FILE'
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

    public function testInvalidCsvData(): void
    {
        $file = <<<'FILE'
{ "message": "this is actually json" }
FILE;

        $actual = CsvTransformer::transformData($file);
        $expected = [];

        $this->assertEquals($expected, $actual);
    }
}
