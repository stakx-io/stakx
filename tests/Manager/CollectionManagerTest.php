<?php declare(strict_types=1);

/**
 * @copyright 2018 Vladimir Jimenez
 * @license   https://github.com/stakx-io/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Test\Manager;

use allejo\stakx\Configuration;
use allejo\stakx\Document\ContentItem;
use allejo\stakx\Filesystem\File;
use allejo\stakx\Filesystem\FilesystemLoader as fs;
use allejo\stakx\Manager\CollectionManager;
use allejo\stakx\RuntimeStatus;
use allejo\stakx\Service;
use allejo\stakx\Test\StakxTestCase;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * @internal
 *
 * @coversNothing
 */
class CollectionManagerTest extends StakxTestCase
{
    private CollectionManager $cm;

    public function setUp(): void
    {
        parent::setUp();

        /** @var Configuration|MockObject $conf */
        $conf = $this->getMockConfiguration();
        $conf
            ->method('hasCollections')
            ->willReturn(true)
        ;
        $conf
            ->method('getCollectionsFolders')
            ->willReturn([
                [
                    'name' => 'My Books',
                    'folder' => fs::path(static::getTestRoot() . '/assets/MyBookCollection'),
                ],
            ])
        ;

        $this->cm = new CollectionManager(
            $this->getMockMarkupEngineManager(),
            $conf,
            $this->getMockTemplateBridge(),
            $this->getMockEventDispatcher(),
            $this->getMockLogger()
        );
        $this->cm->compileManager();
    }

    public function testCollectionCount(): void
    {
        $collections = $this->cm->getCollections();

        $this->assertCount(1, $collections);
    }

    public function testCollectionEmpty(): void
    {
        /** @var Configuration $conf */
        $conf = $this->getMockConfiguration();

        $cm = new CollectionManager(
            $this->getMockMarkupEngineManager(),
            $conf,
            $this->getMockTemplateBridge(),
            $this->getMockEventDispatcher(),
            $this->getMockLogger()
        );
        $cm->parseCollections([]);

        $this->assertEmpty($cm->getCollections());
    }

    public function testCollectionManagerContainsContentItem(): void
    {
        $path = fs::path(static::getTestRoot() . '/assets/MyBookCollection/Tale-of-Despereaux.md');
        $file = new File($path);
        $this->assertTrue($this->cm->isTracked($file->getRelativeFilePath()));

        $path = fs::path(static::getTestRoot() . '/assets/MyBookCollection/Tiger-Rising.md');
        $file = new File($path);
        $this->assertTrue($this->cm->isTracked($file->getRelativeFilePath()));
    }

    public function testCollectionManagerGetContentItem(): void
    {
        $path = fs::path(static::getTestRoot() . '/assets/MyBookCollection/Tiger-Rising.md');
        $file = new File($path);

        $contentItem = $this->cm->getContentItem($file->getRelativeFilePath());

        $this->assertNotNull($contentItem);
        $this->assertEquals('My Books', $contentItem->getNamespace());
        $this->assertEquals('0763680877', $contentItem['isbn_10']);
    }

    public function testCollectionManagerHasDrafts(): void
    {
        $withoutDrafts = count($this->cm->getJailedCollections()['My Books']);

        Service::setRuntimeFlag(RuntimeStatus::USING_DRAFTS);

        $withDrafts = count($this->cm->getJailedCollections()['My Books']);

        $this->assertGreaterThan($withoutDrafts, $withDrafts);
    }

    public function testCollectionManagerAdditionNewContentItem(): void
    {
        $this->createMultipleFrontMatterDocumentsOfType(ContentItem::class, [
            [
                'filename' => 'dark-matter.md',
                'frontmatter' => ['title' => 'Dark Matter'],
            ],
            [
                'filename' => 'sphere.md',
                'frontmatter' => ['title' => 'Sphere'],
            ],
        ]);

        $collections = [
            [
                'name' => 'Sci-Fi',
                'folder' => $this->rootDir->url(),
            ],
        ];

        /** @var Configuration $conf */
        $conf = $this->getMockConfiguration();
        $cm = new CollectionManager(
            $this->getMockMarkupEngineManager(),
            $conf,
            $this->getMockTemplateBridge(),
            $this->getMockEventDispatcher(),
            $this->getMockLogger()
        );

        $cm->parseCollections($collections);
        $this->assertCount(2, $cm->getCollections()['Sci-Fi']);

        //
        // Create a new ContentItem and register it with an existing CollectionManager
        //

        /** @var ContentItem $newItem */
        $newItem = $this->createFrontMatterDocumentOfType(ContentItem::class, 'the-expanse.md', ['title' => 'The Expanse']);
        $pushedItem = $cm->createNewItem($newItem->getFile());

        $this->assertCount(3, $cm->getCollections()['Sci-Fi']);
        $this->assertEquals('Sci-Fi', $pushedItem->getNamespace());
    }
}
