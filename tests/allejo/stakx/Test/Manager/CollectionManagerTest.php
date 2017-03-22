<?php

/**
 * @copyright 2017 Vladimir Jimenez
 * @license   https://github.com/allejo/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Test\Manager;

use allejo\stakx\Manager\CollectionManager;
use allejo\stakx\Test\PHPUnit_Stakx_TestCase;

class CollectionManagerTests extends PHPUnit_Stakx_TestCase
{
    /**
     * @var CollectionManager
     */
    private $cm;

    public function setUp()
    {
        parent::setUp();

        $this->cm = new CollectionManager();
        $this->cm->setLogger($this->getMockLogger());
        $this->cm->parseCollections(array(
            array(
                'name' => 'My Books',
                'folder' => $this->fs->appendPath(__DIR__, '..', 'assets', 'MyBookCollection'),
            ),
        ));
    }

    public function testCollectionCount()
    {
        $collections = $this->cm->getCollections();

        $this->assertCount(1, $collections);
    }

    public function testCollectionEmpty()
    {
        $cm = new CollectionManager();
        $cm->setLogger($this->getMockLogger());
        $cm->parseCollections(array());

        $this->assertEmpty($cm->getCollections());
    }

    public function testCollectionManagerContainsContentItem()
    {
        $this->assertTrue($this->cm->isTracked(
            $this->fs->appendPath('tests', 'allejo', 'stakx', 'Test', 'assets', 'MyBookCollection', 'Tale-of-Despereaux.md')
        ));
        $this->assertTrue($this->cm->isTracked(
            $this->fs->appendPath('tests', 'allejo', 'stakx', 'Test', 'assets', 'MyBookCollection', 'Tiger-Rising.md')
        ));
    }

    public function testCollectionManagerGetContentItem()
    {
        $contentItem = $this->cm->getContentItem(
            $this->fs->appendPath('tests', 'allejo', 'stakx', 'Test', 'assets', 'MyBookCollection', 'Tiger-Rising.md')
        );

        $this->assertNotNull($contentItem);
        $this->assertEquals('My Books', $contentItem->getCollection());
        $this->assertEquals('0763680877', $contentItem['isbn_10']);
    }
}
