<?php

/**
 * @copyright 2017 Vladimir Jimenez
 * @license   https://github.com/allejo/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Test\Manager;

use allejo\stakx\Document\DataItem;
use allejo\stakx\Manager\DataManager;
use allejo\stakx\Test\PHPUnit_Stakx_TestCase;

class DataManagerTest extends PHPUnit_Stakx_TestCase
{
    public function testDataSetParsing()
    {
        $dataSetName = 'calendar';

        $dm = new DataManager();
        $dm->parseDataSets(array(array(
            'name' => $dataSetName,
            'folder' => $this->fs->appendPath(__DIR__, '../assets/MyDataSet'),
        )));

        $this->assertGreaterThan(0, $dm->getDataItems());
        $this->assertGreaterThan(0, $dm->getJailedDataItems());

        /**
         * @var string $key
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
    }

    public function testDataItemParsing()
    {
        $dm = new DataManager();
        $dm->parseDataItems(array(
            $this->fs->appendPath(__DIR__, '../assets/MyDataSet')
        ));

        $this->assertGreaterThan(0, $dm->getDataItems());
        $this->assertGreaterThan(0, $dm->getJailedDataItems());

        /** @var DataItem $item */
        foreach ($dm->getDataItems() as $item)
        {
            $this->assertNull($item->getNamespace());
        }
    }
}
