<?php

/**
 * @copyright 2017 Vladimir Jimenez
 * @license   https://github.com/allejo/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Test\Manager;

use allejo\stakx\Command\BuildableCommand;
use allejo\stakx\Configuration;
use allejo\stakx\Filesystem\File;
use allejo\stakx\Filesystem\FilesystemLoader as fs;
use allejo\stakx\Manager\CollectionManager;
use allejo\stakx\Service;
use allejo\stakx\Test\PHPUnit_Stakx_TestCase;
use PHPUnit_Framework_MockObject_MockObject as MockObject;

class CollectionManagerTests extends PHPUnit_Stakx_TestCase
{
    /** @var CollectionManager */
    private $cm;

    public function setUp()
    {
        parent::setUp();

        /** @var Configuration|MockObject $conf */
        $conf = $this->getMock(Configuration::class);
        $conf
            ->method('hasCollections')
            ->willReturn(true)
            ;
        $conf
            ->method('getCollectionsFolders')
            ->willReturn([
                [
                    'name' => 'My Books',
                    'folder' => fs::appendPath(__DIR__, '..', 'assets', 'MyBookCollection'),
                ],
            ])
        ;

        $this->cm = new CollectionManager($conf);
        $this->cm->setLogger($this->getMockLogger());
        $this->cm->compileManager();
    }

    public function testCollectionCount()
    {
        $collections = $this->cm->getCollections();

        $this->assertCount(1, $collections);
    }

    public function testCollectionEmpty()
    {
        /** @var Configuration $conf */
        $conf = $this->getMock(Configuration::class);

        $cm = new CollectionManager($conf);
        $cm->setLogger($this->getMockLogger());
        $cm->parseCollections(array());

        $this->assertEmpty($cm->getCollections());
    }

    public function testCollectionManagerContainsContentItem()
    {
        $path = fs::path('tests/allejo/stakx/Test/assets/MyBookCollection/Tale-of-Despereaux.md');
        $file = new File($path);
        $this->assertTrue($this->cm->isTracked($file->getRelativeFilePath()));

        $path = fs::path('tests/allejo/stakx/Test/assets/MyBookCollection/Tiger-Rising.md');
        $file = new File($path);
        $this->assertTrue($this->cm->isTracked($file->getRelativeFilePath()));
    }

    public function testCollectionManagerGetContentItem()
    {
        $path = fs::path('tests/allejo/stakx/Test/assets/MyBookCollection/Tiger-Rising.md');
        $file = new File($path);

        $contentItem = $this->cm->getContentItem($file->getRelativeFilePath());

        $this->assertNotNull($contentItem);
        $this->assertEquals('My Books', $contentItem->getNamespace());
        $this->assertEquals('0763680877', $contentItem['isbn_10']);
    }

    public function testCollectionManagerHasDrafts()
    {
        $withoutDrafts = count($this->cm->getJailedCollections()['My Books']);

        Service::setParameter(BuildableCommand::USE_DRAFTS, true);

        $withDrafts = count($this->cm->getJailedCollections()['My Books']);

        $this->assertGreaterThan($withoutDrafts, $withDrafts);
    }
}
