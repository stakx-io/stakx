<?php

/**
 * @copyright 2017 Vladimir Jimenez
 * @license   https://github.com/allejo/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Test\Document;

use allejo\stakx\Document\DataItem;
use allejo\stakx\Test\PHPUnit_Stakx_TestCase;

class DataItemTest extends PHPUnit_Stakx_TestCase
{
    public function testJsonAsDataItem()
    {
        $jsonFile = <<<LINE
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
        /** @var DataItem $dataItem */
        $dataItem = $this->createBlankFile('my-sample-JSON.json', DataItem::class, $jsonFile);

        $this->assertEquals('my-sample-JSON', $dataItem->getName());
        $this->assertEquals(array(1, 2, 3), $dataItem['array']);
        $this->assertTrue($dataItem['boolean']);
        $this->assertNull($dataItem['null']);
        $this->assertEquals(123, $dataItem['number']);
        $this->assertEquals('Hello World', $dataItem['string']);

        $this->assertEquals(array(
            'array' => array(1, 2, 3),
            'boolean' => true,
            'null' => null,
            'number' => 123,
            'object' => array(
                'a' => 'b',
                'c' => 'd',
                'e' => 'f',
            ),
            'string' => 'Hello World',
        ), $dataItem->getData());
    }

    public function testCsvAsDataItem()
    {
        $csvFile = <<<LINE
id,name,gender
1,John Doe,M
2,Jane Doe,F
LINE;
        /** @var DataItem $dataItem */
        $dataItem = $this->createBlankFile('my-file.csv', DataItem::class, $csvFile);
        $jailItem = $dataItem->createJail();

        $this->assertEquals($dataItem[0], $jailItem[0]);
        $this->assertEquals($dataItem->getIterator(), $jailItem->getIterator());
        $this->assertEquals('my-file', $jailItem->getName());
        $this->assertEquals('csv', $jailItem->getExtension());

        $this->assertEquals(array(
            array(
                'id' => 1,
                'name' => 'John Doe',
                'gender' => 'M',
            ),
            array(
                'id' => 2,
                'name' => 'Jane Doe',
                'gender' => 'F',
            ),
        ), $dataItem->getData());
    }
}
