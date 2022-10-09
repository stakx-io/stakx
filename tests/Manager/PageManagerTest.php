<?php declare(strict_types=1);

/**
 * @copyright 2018 Vladimir Jimenez
 * @license   https://github.com/stakx-io/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Test\Manager;

use allejo\stakx\Configuration;
use allejo\stakx\Document\ContentItem;
use allejo\stakx\Document\DynamicPageView;
use allejo\stakx\Document\JailedDocument;
use allejo\stakx\Document\StaticPageView;
use allejo\stakx\Exception\CollectionNotFoundException;
use allejo\stakx\Manager\CollectionManager;
use allejo\stakx\Manager\PageManager;
use allejo\stakx\Test\StakxTestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;

/**
 * @internal
 *
 * @coversNothing
 */
class PageManagerTest extends StakxTestCase
{
    public function testStaticPageViewTitles(): void
    {
        $this->staticPageViewsProvider();

        $pageManager = new PageManager(
            $this->getMockConfiguration(),
            $this->getMockCollectionManager(),
            $this->getMockDataManager(),
            $this->getMockAssetManager(),
            $this->getMockEventDispatcher(),
            $this->getMockLogger()
        );
        $pageManager->compileManager();

        $this->assertCount(3, $pageManager->getPageViewsFlattened());
        $this->assertCount(2, $pageManager->getStaticPageViews());
    }

    public function testDynamicPageViewCollectionFound(): void
    {
        $this->createFrontMatterDocumentOfType(DynamicPageView::class, 'dynamic.html.twig', [
            'collection' => 'books',
            'permalink' => '/blog/%title/',
        ]);

        $pageManager = new PageManager(
            $this->getMockConfiguration(),
            $this->getCollectionManager(),
            $this->getMockDataManager(),
            $this->getMockAssetManager(),
            $this->getMockEventDispatcher(),
            $this->getMockLogger()
        );
        $pageManager->compileManager();

        $pageViews = $pageManager->getPageViewsFlattened();

        $this->assertCount(1, $pageViews);
        $this->assertInstanceOf(DynamicPageView::class, current($pageViews));
    }

    public function testDynamicPageViewCollectionNotFound(): void
    {
        $this->expectException(CollectionNotFoundException::class);

        $this->createFrontMatterDocumentOfType(DynamicPageView::class, 'dynamic.html.twig', [
            'collection' => 'non-existent',
            'permalink' => '/blog/%title/',
        ]);

        $pageManager = new PageManager(
            $this->getMockConfiguration(),
            $this->getCollectionManager(),
            $this->getMockDataManager(),
            $this->getMockAssetManager(),
            $this->getMockEventDispatcher(),
            $this->getMockLogger()
        );
        $pageManager->compileManager();
    }

    public function testPageViewsContentItems(): void
    {
        $this->createFrontMatterDocumentOfType(DynamicPageView::class, 'dynamic.html.twig', [
            'collection' => 'books',
            'permalink' => '/blog/%title/',
        ]);

        $collectionManager = $this->getCollectionManager();
        $pageManager = new PageManager(
            $this->getMockConfiguration(),
            $collectionManager,
            $this->getMockDataManager(),
            $this->getMockAssetManager(),
            $this->getMockEventDispatcher(),
            $this->getMockLogger()
        );
        $pageManager->compileManager();

        $pageViews = $pageManager->getPageViewsFlattened();
        $pageView = current($pageViews);
        $contentItems = $pageView->getCollectableItems();

        $this->assertEquals($collectionManager->getCollections()['books'], $contentItems);
    }

    public function testJailedStaticPageViews(): void
    {
        $this->staticPageViewsProvider();

        $pageManager = new PageManager(
            $this->getMockConfiguration(),
            $this->getMockCollectionManager(),
            $this->getMockDataManager(),
            $this->getMockAssetManager(),
            $this->getMockEventDispatcher(),
            $this->getMockLogger()
        );
        $pageManager->compileManager();

        $pageViews = $pageManager->getJailedStaticPageViews();

        $this->assertCount(2, $pageViews);
        $this->assertInstanceOf(JailedDocument::class, current($pageViews));
    }

    public function testAddingContentItemToPageView(): void
    {
        $this->createFrontMatterDocumentOfType(DynamicPageView::class, 'dynamic.html.twig', [
            'collection' => 'books',
            'permalink' => '/blog/%title/',
        ]);

        $pageManager = new PageManager(
            $this->getMockConfiguration(),
            $this->getCollectionManager(),
            $this->getMockDataManager(),
            $this->getMockAssetManager(),
            $this->getMockEventDispatcher(),
            $this->getMockLogger()
        );
        $pageManager->compileManager();

        $pageViews = $pageManager->getPageViewsFlattened();

        /** @var DynamicPageView $pageView */
        $pageView = current($pageViews);

        $originalCount = count($pageView->getCollectableItems());
        $this->assertGreaterThan(0, $originalCount);

        /** @var ContentItem $contentItem */
        $contentItem = $this->createFrontMatterDocumentOfType(ContentItem::class);
        $contentItem->setNamespace('books');

        $pageManager->trackNewContentItem($contentItem);

        $this->assertCount($originalCount + 1, $pageView->getCollectableItems());
    }

    public function testWarningThrownWhenPageViewFolderNotFound(): void
    {
        $this->expectException(FileNotFoundException::class);

        /** @var Configuration|MockObject $conf */
        $conf = parent::getMockConfiguration();
        $conf
            ->method('getPageViewFolders')
            ->willReturn(['non-existent'])
        ;

        $pageManager = new PageManager(
            $conf,
            $this->getMockCollectionManager(),
            $this->getMockDataManager(),
            $this->getMockAssetManager(),
            $this->getMockEventDispatcher(),
            $this->getMockLogger()
        );
        $pageManager->compileManager();

        $this->assertCount(0, $pageManager->getPageViewsFlattened());
    }

    protected function getMockConfiguration(): Configuration|MockObject
    {
        $mock = parent::getMockConfiguration();
        $mock
            ->method('getPageViewFolders')
            ->willReturn([
                $this->rootDir->url(),
            ])
        ;

        return $mock;
    }

    private function getCollectionManager(): CollectionManager
    {
        $cm = new CollectionManager(
            $this->getMockMarkupEngineManager(),
            $this->getMockConfiguration(),
            $this->getMockTemplateBridge(),
            $this->getMockEventDispatcher(),
            $this->getMockLogger()
        );
        $cm->parseCollections([
            [
                'name' => 'books',
                'folder' => 'tests/assets/MyBookCollection/',
            ],
        ]);

        return $cm;
    }

    /**
     * @return StaticPageView[]
     */
    private function staticPageViewsProvider(): array
    {
        return $this->createMultipleFrontMatterDocumentsOfType(StaticPageView::class, [
            [
                'filename' => 'pageview-1.html.twig',
                'frontmatter' => ['title' => 'Hello World'],
            ],
            [
                'filename' => 'pageview-2.html.twig',
                'frontmatter' => [],
            ],
            [
                'filename' => 'pageview-3.html.twig',
                'frontmatter' => ['title' => 'Lorem Ipsum'],
            ],
        ]);
    }
}
