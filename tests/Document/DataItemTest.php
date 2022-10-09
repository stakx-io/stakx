<?php declare(strict_types=1);

/**
 * @copyright 2018 Vladimir Jimenez
 * @license   https://github.com/stakx-io/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Test\Document;

use allejo\stakx\DataTransformer\CsvTransformer;
use allejo\stakx\DataTransformer\DataTransformerManager;
use allejo\stakx\DataTransformer\JsonTransformer;
use allejo\stakx\DataTransformer\XmlTransformer;
use allejo\stakx\DataTransformer\YamlTransformer;
use allejo\stakx\Document\ContentItem;
use allejo\stakx\Document\DataItem;
use allejo\stakx\Exception\UnsupportedDataTypeException;
use allejo\stakx\Filesystem\File;
use allejo\stakx\Test\StakxTestCase;
use DateTime;
use DateTimeZone;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;

/**
 * @internal
 *
 * @coversNothing
 */
class DataItemTest extends StakxTestCase
{
    public function testJsonAsDataItem(): void
    {
        $jsonFile = <<<'LINE'
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
        $dataItem = $this->createDocumentOfType(DataItem::class, 'my-sample-JSON.json', $jsonFile);
        $dataItem->setDataTransformer($this->getDataTransformerManager());
        $jailItem = $dataItem->createJail();

        $this->assertEquals('my-sample-JSON', $dataItem->getBasename());
        $this->assertEquals([1, 2, 3], $dataItem['array']);
        $this->assertTrue($dataItem['boolean']);
        $this->assertNull($dataItem['null']);
        $this->assertEquals(123, $dataItem['number']);
        $this->assertEquals('Hello World', $dataItem['string']);

        foreach (['array', 'boolean', 'null', 'number', 'string'] as $key) {
            $this->assertEquals($dataItem[$key], $jailItem[$key]);
        }

        $this->assertEquals([
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
        ], $dataItem->getContent());
    }

    public function testCsvAsDataItem(): void
    {
        $csvFile = <<<'LINE'
id,name,gender
1,John Doe,M
2,Jane Doe,F
LINE;

        /** @var DataItem $dataItem */
        $dataItem = $this->createDocumentOfType(DataItem::class, 'my-file.csv', $csvFile);
        $dataItem->setDataTransformer($this->getDataTransformerManager());
        $jailItem = $dataItem->createJail();

        $this->assertEquals($dataItem[0], $jailItem[0]);
        $this->assertEquals($dataItem->getIterator(), $jailItem->getIterator());
        $this->assertEquals($dataItem->getBasename(), $jailItem->getBasename());
        $this->assertEquals('csv', $jailItem->getExtension());
        $this->assertEquals($dataItem->getExtension(), $jailItem->getExtension());

        $this->assertEquals([
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
        ], $dataItem->getContent());
    }

    public function testYamlAsDataItem(): void
    {
        $yamlFile = <<<'LINE'
month: January
events:
  - 2017-01-01
  - 2017-01-18
  - 2017-01-19
  - 2017-01-30
LINE;

        /** @var DataItem $dataItem */
        $dataItem = $this->createDocumentOfType(DataItem::class, 'my-yaml.yml', $yamlFile);
        $yamlExtension = $this->createDocumentOfType(DataItem::class, 'my-file.yaml', $yamlFile);

        $dataItem->setDataTransformer($this->getDataTransformerManager());
        $yamlExtension->setDataTransformer($this->getDataTransformerManager());

        $this->assertEquals($dataItem->getContent(), $yamlExtension->getContent());

        $tz = new DateTimeZone('UTC');

        $this->assertEquals([
            'month' => 'January',
            'events' => [
                new DateTime('2017-01-01', $tz),
                new DateTime('2017-01-18', $tz),
                new DateTime('2017-01-19', $tz),
                new DateTime('2017-01-30', $tz),
            ],
        ], $dataItem->getContent());
    }

    public function testXmlAsDataItem(): void
    {
        $xmlFile = <<<'LINE'
<note>
    <to attribute="attr value">Tove</to>
    <from>Jani</from>
    <heading>Reminder</heading>
    <body>Don't forget me this weekend!</body>
</note>
LINE;

        /** @var DataItem $dataItem */
        $dataItem = $this->createDocumentOfType(DataItem::class, 'my-data.xml', $xmlFile);
        $dataItem->setDataTransformer($this->getDataTransformerManager());

        $this->assertEquals([
            'to' => 'Tove',
            'from' => 'Jani',
            'heading' => 'Reminder',
            'body' => "Don't forget me this weekend!",
        ], $dataItem->getContent());
    }

    public function testDataItemDoesNotExist(): void
    {
        $this->expectException(FileNotFoundException::class);

        new DataItem(new File('/path/to/my-data.yml'));
    }

    public function testUnsupportedDataItemExtension(): void
    {
        $this->expectException(UnsupportedDataTypeException::class);

        /** @var ContentItem $contentItem */
        $contentItem = $this->createFrontMatterDocumentOfType(ContentItem::class);

        $dataItem = new DataItem($contentItem->getFile());
        $dataItem->setDataTransformer($this->getDataTransformerManager());
    }

    private function getDataTransformerManager(): DataTransformerManager
    {
        $manager = new DataTransformerManager();
        $manager->addDataTransformer(new CsvTransformer());
        $manager->addDataTransformer(new JsonTransformer());
        $manager->addDataTransformer(new XmlTransformer());
        $manager->addDataTransformer(new YamlTransformer());

        return $manager;
    }
}
