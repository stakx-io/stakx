<?php

namespace allejo\stakx\tests;

use allejo\stakx\Manager\CollectionManager;
use PHPUnit_Framework_TestCase;
use Symfony\Component\Console\Output\OutputInterface;

class CollectionManagerTests extends PHPUnit_Framework_TestCase
{
    /**
     * @var CollectionManager
     */
    private $cm;

    public function setUp ()
    {
        $this->cm = new CollectionManager();
        $this->cm->setConsoleOutput($this->outputMock());
        $this->cm->parseCollections(array(
            array(
                'name' => 'Sample',
                'folder' => __DIR__ . '/SampleCollection'
            )
        ));
    }

    public function testCollectionCount ()
    {
        $collections = $this->cm->getCollections();

        $this->assertCount(1, $collections);
    }

    public function testContentItemCount ()
    {
        $collections = $this->cm->getFlatCollections();

        $this->assertCount(3, $collections);
    }

    public function testContentItemCollection ()
    {
        $contentItems = $this->cm->getFlatCollections();

        foreach ($contentItems as $contentItem)
        {
            $this->assertEquals('Sample', $contentItem->getCollection());
        }
    }

    public function testCollectionEmpty ()
    {
        $cm = new CollectionManager();
        $cm->setConsoleOutput($this->outputMock());
        $cm->parseCollections(array());

        $this->assertEmpty($cm->getCollections());
        $this->assertEmpty($cm->getFlatCollections());
    }

    public function testCollectionManagerContainsContentItem ()
    {
        $this->assertTrue($this->cm->isTrackedByManager('Tale-of-Despereaux'));
        $this->assertTrue($this->cm->isTrackedByManager('Tiger-Rising'));
    }

    public function testCollectionManagerGetContentItem ()
    {
        $contentItem = $this->cm->getContentItem('Tiger-Rising');

        $this->assertNotNull($contentItem);
        $this->assertEquals('Sample', $contentItem->getCollection());
        $this->assertEquals('0763680877', $contentItem->isbn_10);
    }

    /**
     * @return OutputInterface
     */
    private function outputMock ()
    {
        return $this->getMock(OutputInterface::class);
    }
}
