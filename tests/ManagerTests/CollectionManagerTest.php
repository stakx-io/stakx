<?php

namespace allejo\stakx\tests;

use allejo\stakx\Manager\CollectionManager;
use allejo\stakx\System\Filesystem;
use PHPUnit_Framework_TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CollectionManagerTests extends PHPUnit_Framework_TestCase
{
    /**
     * @var CollectionManager
     */
    private $cm;

    /**
     * @var Filesystem
     */
    private $fs;

    public function setUp ()
    {
        $this->fs = new Filesystem();

        $this->cm = new CollectionManager();
        $this->cm->setLogger($this->loggerMock());
        $this->cm->parseCollections(array(
            array(
                'name' => 'Sample',
                'folder' => __DIR__ . DIRECTORY_SEPARATOR . 'SampleCollection'
            )
        ));
    }

    public function testCollectionCount ()
    {
        $collections = $this->cm->getCollections();

        $this->assertCount(1, $collections);
    }

    public function testCollectionEmpty ()
    {
        $cm = new CollectionManager();
        $cm->setLogger($this->loggerMock());
        $cm->parseCollections(array());

        $this->assertEmpty($cm->getCollections());
    }

    public function testCollectionManagerContainsContentItem ()
    {
        $this->assertTrue($this->cm->isTracked(
            $this->fs->appendPath('tests', 'ManagerTests', 'SampleCollection', 'Tale-of-Despereaux.md')
        ));
        $this->assertTrue($this->cm->isTracked(
            $this->fs->appendPath('tests', 'ManagerTests', 'SampleCollection', 'Tiger-Rising.md')
        ));
    }

    public function testCollectionManagerGetContentItem ()
    {
        $contentItem = $this->cm->getContentItem(
            $this->fs->appendPath('tests', 'ManagerTests', 'SampleCollection', 'Tiger-Rising.md')
        );

        $this->assertNotNull($contentItem);
        $this->assertEquals('Sample', $contentItem->getCollection());
        $this->assertEquals('0763680877', $contentItem->isbn_10);
    }

    /**
     * @return LoggerInterface
     */
    private function loggerMock ()
    {
        return $this->getMock(LoggerInterface::class);
    }
}
