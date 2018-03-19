<?php

/**
 * @copyright 2017 Vladimir Jimenez
 * @license   https://github.com/allejo/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Test\Manager;

use allejo\stakx\Command\BuildableCommand;
use allejo\stakx\Configuration;
use allejo\stakx\Document\ContentItem;
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
                    'folder' => fs::path($this->getTestRoot() . '/assets/MyBookCollection'),
                ],
            ])
        ;

        $this->cm = new CollectionManager($conf, $this->getMockEventDistpatcher(), $this->getMockLogger());
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

        $cm = new CollectionManager($conf, $this->getMockEventDistpatcher(), $this->getMockLogger());
        $cm->parseCollections([]);

        $this->assertEmpty($cm->getCollections());
    }

    public function testCollectionManagerContainsContentItem()
    {
        $path = fs::path($this->getTestRoot() . '/assets/MyBookCollection/Tale-of-Despereaux.md');
        $file = new File($path);
        $this->assertTrue($this->cm->isTracked($file->getRelativeFilePath()));

        $path = fs::path($this->getTestRoot() . '/assets/MyBookCollection/Tiger-Rising.md');
        $file = new File($path);
        $this->assertTrue($this->cm->isTracked($file->getRelativeFilePath()));
    }

    public function testCollectionManagerGetContentItem()
    {
        $path = fs::path($this->getTestRoot() . '/assets/MyBookCollection/Tiger-Rising.md');
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

    public function testCollectionManagerAdditionNewContentItem()
    {
        $this->createMultipleVirtualFiles(ContentItem::class, [
            [
                'frontmatter' => [ 'title' => 'Dark Matter' ],
            ],
            [
                'frontmatter' => [ 'title' => 'Sphere' ]
            ]
        ]);

        $collections = [
            [
                'name' => 'Sci-Fi',
                'folder' => $this->rootDir->url(),
            ],
        ];

        /** @var Configuration $conf */
        $conf = $this->getMock(Configuration::class);
        $cm = new CollectionManager($conf, $this->getMockEventDistpatcher(), $this->getMockLogger());

        $cm->parseCollections($collections);
        $this->assertCount(2, $cm->getCollections()['Sci-Fi']);

        //
        // Create a new ContentItem and register it with an existing CollectionManager
        //

        /** @var ContentItem $newItem */
        $newItem = $this->createVirtualFrontMatterFile(ContentItem::class, [ 'title' => 'The Expanse' ]);
        $pushedItem = $cm->createNewItem($newItem->getFile());

        $this->assertCount(3, $cm->getCollections()['Sci-Fi']);
        $this->assertEquals('Sci-Fi', $pushedItem->getNamespace());
    }
}
