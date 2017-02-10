<?php

namespace allejo\stakx\tests;

use allejo\stakx\Manager\CollectionManager;
use allejo\stakx\System\Filesystem;
use PHPUnit_Framework_TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CollectionManagerTests extends \PHPUnit_Stakx_TestCase
{
    /**
     * @var CollectionManager
     */
    private $cm;

    public function setUp ()
    {
        $this->fs = new Filesystem();

        $this->cm = new CollectionManager();
        $this->cm->setLogger($this->loggerMock());
        $this->cm->parseCollections(array(
            array(
                'name' => 'My Books',
                'folder' => $this->fs->appendPath(__DIR__, 'assets', 'MyBookCollection')
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
            $this->fs->appendPath('tests', 'assets', 'MyBookCollection', 'Tale-of-Despereaux.md')
        ));
        $this->assertTrue($this->cm->isTracked(
            $this->fs->appendPath('tests', 'assets', 'MyBookCollection', 'Tiger-Rising.md')
        ));
    }

    public function testCollectionManagerGetContentItem ()
    {
        $contentItem = $this->cm->getContentItem(
            $this->fs->appendPath('tests', 'assets', 'MyBookCollection', 'Tiger-Rising.md')
        );

        $this->assertNotNull($contentItem);
        $this->assertEquals('My Books', $contentItem->getCollection());
        $this->assertEquals('0763680877', $contentItem->isbn_10);
    }
}
