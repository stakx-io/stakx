<?php

/**
 * @copyright 2017 Vladimir Jimenez
 * @license   https://github.com/allejo/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Test\Manager;

use allejo\stakx\Document\ContentItem;
use allejo\stakx\Document\DynamicPageView;
use allejo\stakx\Document\JailedDocument;
use allejo\stakx\Document\PageView;
use allejo\stakx\Exception\CollectionNotFoundException;
use allejo\stakx\Manager\PageManager;
use allejo\stakx\Test\PHPUnit_Stakx_TestCase;
use allejo\stakx\Test\StreamInterceptor;

class PageManagerTest extends PHPUnit_Stakx_TestCase
{
    public function testDynamicPageViewCollectionFound()
    {
        $this->createVirtualFile(DynamicPageView::class, array(
            'collection' => 'books',
            'permalink' => '/blog/%title/',
        ));
        $collections = $this->bookCollectionProvider();
        $pageManager = new PageManager();
        $pageManager->setLogger($this->getMockLogger());
        $pageManager->setCollections($collections);
        $pageManager->parsePageViews(array($this->rootDir->url()));

        $pageViews = $pageManager->getAllPageViews();

        $this->assertCount(1, $pageViews);
        $this->assertInstanceOf(DynamicPageView::class, current($pageViews));
    }

    public function testPageViewsContentItems()
    {
        $this->createVirtualFile(DynamicPageView::class, array(
            'collection' => 'books',
            'permalink' => '/blog/%title/',
        ));
        $collections = $this->bookCollectionProvider();

        $pageManager = new PageManager();
        $pageManager->setLogger($this->getMockLogger());
        $pageManager->setCollections($collections);
        $pageManager->parsePageViews(array($this->rootDir->url()));

        $pageViews = $pageManager->getAllPageViews();
        $pageView = current($pageViews);
        $contentItems = $pageView->getRepeatableItems();

        $this->assertEquals($collections['books'], $contentItems);
    }

    public function testDynamicPageViewCollectionNotFound()
    {
        $this->setExpectedException(CollectionNotFoundException::class);

        $this->createVirtualFile(DynamicPageView::class, array(
            'collection' => 'non-existent',
            'permalink' => '/blog/%title/',
        ));
        $collections = $this->bookCollectionProvider();
        $pageManager = new PageManager();
        $pageManager->setLogger($this->getMockLogger());
        $pageManager->setCollections($collections);
        $pageManager->parsePageViews(array($this->rootDir->url()));
    }

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

        $pageManager = new PageManager();
        $pageManager->setLogger($this->getMockLogger());
        $pageManager->parsePageViews(array($this->rootDir->url()));

        $this->assertCount(3, $pageManager->getAllPageViews());
        $this->assertCount(2, $pageManager->getStaticPageViews());
    }

    public function testJailedStaticPageViews()
    {
        $this->staticPageViewsProvider();

        $pageManager = new PageManager();
        $pageManager->setLogger($this->getMockLogger());
        $pageManager->parsePageViews(array($this->rootDir->url()));

        $pageViews = $pageManager->getJailedStaticPageViews();

        $this->assertCount(2, $pageViews);
        $this->assertInstanceOf(JailedDocument::class, current($pageViews));
    }

    public function testAddingContentItemToPageView()
    {
        $this->createVirtualFile(DynamicPageView::class, array(
            'collection' => 'books',
            'permalink' => '/blog/%title/',
        ));
        $collections = $this->bookCollectionProvider();

        $pageManager = new PageManager();
        $pageManager->setLogger($this->getMockLogger());
        $pageManager->setCollections($collections);
        $pageManager->parsePageViews(array($this->rootDir->url()));
        $pageViews = $pageManager->getAllPageViews();

        /** @var DynamicPageView $pageView */
        $pageView = current($pageViews);

        $originalCount = count($pageView->getRepeatableItems());
        $this->assertGreaterThan(0, $originalCount);

        /** @var ContentItem $contentItem */
        $contentItem = $this->createVirtualFile(ContentItem::class);
        $contentItem->setNamespace('books');

        $pageManager->trackNewContentItem($contentItem);

        $this->assertCount($originalCount + 1, $pageView->getRepeatableItems());
    }

    public function testWarningThrownWhenPageViewFolderNotFound()
    {
        $logger = $this->getReadableLogger();

        $pageManager = new PageManager();
        $pageManager->setLogger($logger);
        $pageManager->parsePageViews(array('non-existent'));

        $this->assertContains("The 'non-existent' folder could not be found", StreamInterceptor::$output);
        $this->assertCount(0, $pageManager->getAllPageViews());
    }
}
