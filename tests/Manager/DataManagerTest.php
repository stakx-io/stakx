<?php

/**
 * @copyright 2018 Vladimir Jimenez
 * @license   https://github.com/stakx-io/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Test\Manager;

use allejo\stakx\DataTransformer\CsvTransformer;
use allejo\stakx\DataTransformer\DataTransformerManager;
use allejo\stakx\DataTransformer\JsonTransformer;
use allejo\stakx\DataTransformer\XmlTransformer;
use allejo\stakx\DataTransformer\YamlTransformer;
use allejo\stakx\Document\DataItem;
use allejo\stakx\Filesystem\FilesystemLoader as fs;
use allejo\stakx\Manager\DataManager;
use allejo\stakx\Test\PHPUnit_Stakx_TestCase;

class DataManagerTest extends PHPUnit_Stakx_TestCase
{
    public function getDataTransformerManager()
    {
        $manager = new DataTransformerManager();
        $manager->addDataTransformer(new CsvTransformer());
        $manager->addDataTransformer(new JsonTransformer());
        $manager->addDataTransformer(new XmlTransformer());
        $manager->addDataTransformer(new YamlTransformer());

        return $manager;
    }

    public function testDataSetParsing()
    {
        $dataSetName = 'calendar';

        $dm = new DataManager(
            $this->getDataTransformerManager(),
            $this->getMockConfiguration(),
            $this->getMockEventDistpatcher(),
            $this->getMockLogger()
        );
        $dm->parseDataSets([[
            'name' => $dataSetName,
            'folder' => fs::appendPath(__DIR__, '../assets/MyDataSet'),
        ]]);

        $this->assertGreaterThan(0, $dm->getDataItems());
        $this->assertGreaterThan(0, $dm->getJailedDataItems());

        /**
         * @var string
         * @var DataItem[] $items
         */
        foreach ($dm->getDataItems() as $key => $items)
        {
            $this->assertEquals($key, $dataSetName);

            foreach ($items as $item)
            {
                $this->assertInstanceOf(DataItem::class, $item);
                $this->assertEquals($dataSetName, $item->getNamespace());
            }
        }

        $prison = $dm->getJailedDataItems();

        $this->assertCount(1, $prison);
        $this->assertArrayHasKey('calendar', $prison);
    }

    public function testDataItemParsing()
    {
        $dm = new DataManager(
            $this->getDataTransformerManager(),
            $this->getMockConfiguration(),
            $this->getMockEventDistpatcher(),
            $this->getMockLogger()
        );
        $dm->parseDataItems([
            fs::appendPath(__DIR__, '../assets/MyDataSet'),
        ]);

        $this->assertGreaterThan(0, $dm->getDataItems());
        $this->assertGreaterThan(0, $dm->getJailedDataItems());

        /** @var DataItem $item */
        foreach ($dm->getDataItems() as $item)
        {
            $this->assertNull($item->getNamespace());
        }

        // Test jail items
        $prison = $dm->getJailedDataItems();

        $this->assertArrayHasKey('january', $prison);
        $this->assertArrayHasKey('december', $prison);
    }
}
