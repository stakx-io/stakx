<?php

/**
 * @copyright 2017 Vladimir Jimenez
 * @license   https://github.com/allejo/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Test\Manager;

use allejo\stakx\Configuration;
use allejo\stakx\Document\ContentItem;
use allejo\stakx\Document\DynamicPageView;
use allejo\stakx\Document\JailedDocument;
use allejo\stakx\Document\PageView;
use allejo\stakx\Exception\CollectionNotFoundException;
use allejo\stakx\Manager\CollectionManager;
use allejo\stakx\Manager\PageManager;
use allejo\stakx\Test\PHPUnit_Stakx_TestCase;
use allejo\stakx\Test\StreamInterceptor;
use PHPUnit_Framework_MockObject_MockObject as MockObject;

class PageManagerTest extends PHPUnit_Stakx_TestCase
{
    /**
     * @return Configuration|MockObject
     */
    private function getMockConfiguration()
    {
        $mock = $this->getMock(Configuration::class);
        $mock
            ->method('getPageViewFolders')
            ->willReturn([
                $this->rootDir->url()
            ])
        ;

        return $mock;
    }

    /**
     * @return CollectionManager
     */
    private function getCollectionManager()
    {
        $cm = new CollectionManager();
        $cm->setLogger($this->getMockLogger());
        $cm->parseCollections([
            [
                'name' => 'books',
                'folder' => 'tests/allejo/stakx/Test/assets/MyBookCollection/',
            ],
        ]);

        return $cm;
    }

    /**
     * @return PageView[]
     */
    private function staticPageViewsProvider()
    {
        return $this->createMultipleVirtualFiles(PageView::class, array(
            array(
                'filename' => 'pageview-1.html.twig',
                'frontmatter' => array('title' => 'Hello World'),
            ),
            array(
                'filename' => 'pageview-2.html.twig',
                'frontmatter' => array(),
            ),
            array(
                'filename' => 'pageview-3.html.twig',
                'frontmatter' => array('title' => 'Lorem Ipsum'),
            ),
        ));
    }

    public function testStaticPageViewTitles()
    {
        $this->staticPageViewsProvider();

        $pageManager = new PageManager($this->getMockConfiguration());
        $pageManager->compileManager();

        $this->assertCount(3, $pageManager->getPageViewsFlattened());
        $this->assertCount(2, $pageManager->getStaticPageViews());
    }

    public function testDynamicPageViewCollectionFound()
    {
        $this->createVirtualFrontMatterFile(DynamicPageView::class, array(
            'collection' => 'books',
            'permalink' => '/blog/%title/',
        ));

        $pageManager = new PageManager($this->getMockConfiguration(), $this->getCollectionManager());
        $pageManager->compileManager();

        $pageViews = $pageManager->getPageViewsFlattened();

        $this->assertCount(1, $pageViews);
        $this->assertInstanceOf(DynamicPageView::class, current($pageViews));
    }

    public function testDynamicPageViewCollectionNotFound()
    {
        $this->setExpectedException(CollectionNotFoundException::class);

        $this->createVirtualFrontMatterFile(DynamicPageView::class, array(
            'collection' => 'non-existent',
            'permalink' => '/blog/%title/',
        ));

        $pageManager = new PageManager($this->getMockConfiguration(), $this->getCollectionManager());
        $pageManager->compileManager();
    }

    public function testPageViewsContentItems()
    {
        $this->createVirtualFrontMatterFile(DynamicPageView::class, array(
            'collection' => 'books',
            'permalink' => '/blog/%title/',
        ));

        $collectionManager = $this->getCollectionManager();
        $pageManager = new PageManager($this->getMockConfiguration(), $collectionManager);
        $pageManager->compileManager();

        $pageViews = $pageManager->getPageViewsFlattened();
        $pageView = current($pageViews);
        $contentItems = $pageView->getRepeatableItems();

        $this->assertEquals($collectionManager->getCollections()['books'], $contentItems);
    }

    public function testJailedStaticPageViews()
    {
        $this->staticPageViewsProvider();

        $pageManager = new PageManager($this->getMockConfiguration());
        $pageManager->compileManager();

        $pageViews = $pageManager->getJailedStaticPageViews();

        $this->assertCount(2, $pageViews);
        $this->assertInstanceOf(JailedDocument::class, current($pageViews));
    }

    public function testAddingContentItemToPageView()
    {
        $this->createVirtualFrontMatterFile(DynamicPageView::class, array(
            'collection' => 'books',
            'permalink' => '/blog/%title/',
        ));

        $pageManager = new PageManager($this->getMockConfiguration(), $this->getCollectionManager());
        $pageManager->compileManager();

        $pageViews = $pageManager->getPageViewsFlattened();

        /** @var DynamicPageView $pageView */
        $pageView = current($pageViews);

        $originalCount = count($pageView->getRepeatableItems());
        $this->assertGreaterThan(0, $originalCount);

        /** @var ContentItem $contentItem */
        $contentItem = $this->createVirtualFrontMatterFile(ContentItem::class);
        $contentItem->setNamespace('books');

        $pageManager->trackNewContentItem($contentItem);

        $this->assertCount($originalCount + 1, $pageView->getRepeatableItems());
    }

    public function testWarningThrownWhenPageViewFolderNotFound()
    {
        /** @var Configuration|MockObject $conf */
        $conf = $this->getMock(Configuration::class);
        $conf
            ->method('getPageViewFolders')
            ->willReturn(['non-existent'])
        ;

        $pageManager = new PageManager($conf);
        $pageManager->setLogger($this->getReadableLogger());
        $pageManager->compileManager();

        $this->assertContains("The 'non-existent' folder could not be found", StreamInterceptor::$output);
        $this->assertCount(0, $pageManager->getPageViewsFlattened());
    }
}
