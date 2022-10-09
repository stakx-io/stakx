<?php declare(strict_types=1);

/**
 * @copyright 2018 Vladimir Jimenez
 * @license   https://github.com/stakx-io/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Test\DataTransformer;

use allejo\stakx\DataTransformer\JsonTransformer;
use allejo\stakx\Test\StakxTestCase;

/**
 * @internal
 *
 * @coversNothing
 */
class JsonTransformerTest extends StakxTestCase
{
    public function testValidJsonData(): void
    {
        $file = <<<'LINE'
{
  "array": [
    1,
    2,
    3
  ],
  "boolean": true,
  "null": null,
  "number": 123,
  "object": {
    "a": "b",
    "c": "d",
    "e": "f"
  },
  "string": "Hello World"
}
LINE;

        $actual = JsonTransformer::transformData($file);
        $expected = [
            'array' => [1, 2, 3],
            'boolean' => true,
            'null' => null,
            'number' => 123,
            'object' => [
                'a' => 'b',
                'c' => 'd',
                'e' => 'f',
            ],
            'string' => 'Hello World',
        ];

        $this->assertEquals($expected, $actual);
    }

    public function testInvalidJsonData(): void
    {
        $file = <<<'LINE'
invalid JSON
LINE;

        $actual = JsonTransformer::transformData($file);
        $expected = [];

        $this->assertEquals($expected, $actual);
    }
}
