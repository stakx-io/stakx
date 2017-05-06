<?php

/**
 * @copyright 2017 Vladimir Jimenez
 * @license   https://github.com/allejo/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Test\Document;

use allejo\stakx\Document\ContentItem;
use allejo\stakx\Document\DataItem;
use allejo\stakx\Exception\UnsupportedDataTypeException;
use allejo\stakx\Test\PHPUnit_Stakx_TestCase;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;

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
        $jailItem = $dataItem->createJail();

        $this->assertEquals('my-sample-JSON', $dataItem->getObjectName());
        $this->assertEquals(array(1, 2, 3), $dataItem['array']);
        $this->assertTrue($dataItem['boolean']);
        $this->assertNull($dataItem['null']);
        $this->assertEquals(123, $dataItem['number']);
        $this->assertEquals('Hello World', $dataItem['string']);

        foreach (array('array', 'boolean', 'null', 'number', 'string') as $key)
        {
            $this->assertEquals($dataItem[$key], $jailItem[$key]);
        }

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
        $this->assertEquals($dataItem->getObjectName(), $jailItem->getName());
        $this->assertEquals('csv', $jailItem->getExtension());
        $this->assertEquals($dataItem->getExtension(), $jailItem->getExtension());

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

    public function testYamlAsDataItem()
    {
        $yamlFile = <<<LINE
month: January
events:
  - 2017-01-01
  - 2017-01-18
  - 2017-01-19
  - 2017-01-30
LINE;
        /** @var DataItem $dataItem */
        $dataItem = $this->createBlankFile('my-yaml.yml', DataItem::class, $yamlFile);
        $yamlExtension = $this->createBlankFile('my-file.yaml', DataItem::class, $yamlFile);

        $this->assertEquals($dataItem->getData(), $yamlExtension->getData());

        $tz = new \DateTimeZone('UTC');

        $this->assertEquals(array(
            'month' => 'January',
            'events' => array(
                new \DateTime('2017-01-01', $tz),
                new \DateTime('2017-01-18', $tz),
                new \DateTime('2017-01-19', $tz),
                new \DateTime('2017-01-30', $tz),
            ),
        ), $dataItem->getData());
    }

    public function testXmlAsDataItem()
    {
        $xmlFile = <<<LINE
<note>
    <to attribute="attr value">Tove</to>
    <from>Jani</from>
    <heading>Reminder</heading>
    <body>Don't forget me this weekend!</body>
</note>
LINE;
        /** @var DataItem $dataItem */
        $dataItem = $this->createBlankFile('my-data.xml', DataItem::class, $xmlFile);

        $this->assertEquals(array(
            'to' => 'Tove',
            'from' => 'Jani',
            'heading' => 'Reminder',
            'body' => "Don't forget me this weekend!"
        ), $dataItem->getData());
    }

    public function testDataItemDoesNotExist()
    {
        $this->setExpectedException(FileNotFoundException::class);

        new DataItem('/path/to/my-data.yml');
    }

    public function testUnsupportedDataItemExtension()
    {
        $this->setExpectedException(UnsupportedDataTypeException::class);

        $this->createVirtualFile(ContentItem::class);

        // The dummyFile defaults to a `.twig` extension
        new DataItem($this->dummyFile->url());
    }
}
