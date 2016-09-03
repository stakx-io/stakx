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
        $output = $this->getMock(OutputInterface::class);

        $this->cm = new CollectionManager();
        $this->cm->setConsoleOutput($output);
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
}
