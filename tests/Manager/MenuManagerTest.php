<?php

/**
 * @copyright 2018 Vladimir Jimenez
 * @license   https://github.com/stakx-io/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Test\Manager;

use allejo\stakx\Document\JailedDocument;
use allejo\stakx\Document\StaticPageView;
use allejo\stakx\Manager\MenuManager;
use allejo\stakx\Manager\PageManager;
use allejo\stakx\Test\PHPUnit_Stakx_TestCase;

class MenuManagerTest extends PHPUnit_Stakx_TestCase
{
    /** @var MenuManager */
    private $mm;

    public function setUp()
    {
        parent::setUp();

        /** @var PageManager|\PHPUnit_Framework_MockObject_MockObject $pm */
        $pm = $this->getMockBuilder(PageManager::class)
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $this->mm = new MenuManager($pm, $this->getMockEventDistpatcher(), $this->getMockLogger());
    }

    private function menuFrontMatterEvaluator(array &$pageViews)
    {
        foreach ($pageViews as $pageView)
        {
            $pageView->evaluateFrontMatter();
        }
    }

    public function dataProvider_SingleLevelMenu()
    {
        $pageViews = [];
        $pageViews[] = $this->createFrontMatterDocumentOfType(StaticPageView::class, null, ['permalink' => '/about/']);
        $pageViews[] = $this->createFrontMatterDocumentOfType(StaticPageView::class, null, ['permalink' => '/contact/']);
        $pageViews[] = $this->createFrontMatterDocumentOfType(StaticPageView::class, null, ['permalink' => '/blog/']);

        $this->menuFrontMatterEvaluator($pageViews);

        return $pageViews;
    }

    public function dataProvider_SecondLevelMenu()
    {
        $pageViews = [];
        $pageViews[] = $this->createFrontMatterDocumentOfType(StaticPageView::class, null, ['permalink' => '/authors/']);
        $pageViews[] = $this->createFrontMatterDocumentOfType(StaticPageView::class, null, ['permalink' => '/authors/michael/']);
        $pageViews[] = $this->createFrontMatterDocumentOfType(StaticPageView::class, null, ['permalink' => '/authors/jim/']);
        $pageViews[] = $this->createFrontMatterDocumentOfType(StaticPageView::class, null, ['permalink' => '/authors/dwight/']);
        $pageViews[] = $this->createFrontMatterDocumentOfType(StaticPageView::class, null, ['permalink' => '/blog/']);

        $this->menuFrontMatterEvaluator($pageViews);

        return $pageViews;
    }

    public function dataProvider_SecondLevelMenuWithOrphans()
    {
        $pageViews = [];
        $pageViews[] = $this->createFrontMatterDocumentOfType(StaticPageView::class, null, ['permalink' => '/authors/michael/']);
        $pageViews[] = $this->createFrontMatterDocumentOfType(StaticPageView::class, null, ['permalink' => '/authors/jim/']);
        $pageViews[] = $this->createFrontMatterDocumentOfType(StaticPageView::class, null, ['permalink' => '/authors/dwight/']);
        $pageViews[] = $this->createFrontMatterDocumentOfType(StaticPageView::class, null, ['permalink' => '/blog/']);

        $this->menuFrontMatterEvaluator($pageViews);

        return $pageViews;
    }

    public function dataProvider_ThirdLevelMenu()
    {
        $pageViews = [];
        $pageViews[] = $this->createFrontMatterDocumentOfType(StaticPageView::class, null, ['permalink' => '/authors/']);
        $pageViews[] = $this->createFrontMatterDocumentOfType(StaticPageView::class, null, ['permalink' => '/authors/british/']);
        $pageViews[] = $this->createFrontMatterDocumentOfType(StaticPageView::class, null, ['permalink' => '/authors/english/']);
        $pageViews[] = $this->createFrontMatterDocumentOfType(StaticPageView::class, null, ['permalink' => '/authors/english/michael/']);
        $pageViews[] = $this->createFrontMatterDocumentOfType(StaticPageView::class, null, ['permalink' => '/authors/english/jim/']);
        $pageViews[] = $this->createFrontMatterDocumentOfType(StaticPageView::class, null, ['permalink' => '/authors/english/dwight/']);
        $pageViews[] = $this->createFrontMatterDocumentOfType(StaticPageView::class, null, ['permalink' => '/blog/']);

        $this->menuFrontMatterEvaluator($pageViews);

        return $pageViews;
    }

    public function testSiteMenuCount()
    {
        $pageViews = $this->dataProvider_SingleLevelMenu();
        $this->mm->buildFromPageViews($pageViews);

        $this->assertCount(count($pageViews), $this->mm->getSiteMenu());
    }

    public function testSiteMenuIsJailed()
    {
        $pageViews = $this->dataProvider_SingleLevelMenu();
        $this->mm->buildFromPageViews($pageViews);
        $menu = $this->mm->getSiteMenu();

        $this->assertInstanceOf(JailedDocument::class, reset($menu));
    }

    public function testSiteMenuWithChildren()
    {
        $pageViews = $this->dataProvider_SecondLevelMenu();
        $this->mm->buildFromPageViews($pageViews);
        $menu = $this->mm->getSiteMenu();

        $this->assertCount(2, $menu);
        $this->assertCount(3, $menu['authors']->getChildren());
    }

    public function testSiteMenuWithOrphans()
    {
        $pageViews = $this->dataProvider_SecondLevelMenuWithOrphans();
        $this->mm->buildFromPageViews($pageViews);
        $menu = $this->mm->getSiteMenu();

        $this->assertCount(1, $menu);
        $this->assertEquals('blog', array_keys($menu)[0]);
    }

    public function testSiteMenuWithGrandchildren()
    {
        $pageViews = $this->dataProvider_ThirdLevelMenu();
        $this->mm->buildFromPageViews($pageViews);
        $menu = $this->mm->getSiteMenu();

        $this->assertCount(2, $menu);
        $this->assertCount(2, $authors = $menu['authors']->getChildren());
        $this->assertCount(0, $authors['british']->getChildren());
        $this->assertCount(3, $authors['english']->getChildren());
    }

    public function testSiteMenuExclusionWithMenuFrontMatter()
    {
        $pageViews = [];
        $pageViews[] = $this->createFrontMatterDocumentOfType(StaticPageView::class, null, ['permalink' => '/yes/']);
        $pageViews[] = $this->createFrontMatterDocumentOfType(StaticPageView::class, null, ['permalink' => '/yas/', 'menu' => true]);
        $pageViews[] = $this->createFrontMatterDocumentOfType(StaticPageView::class, null, ['permalink' => '/no/', 'menu' => false]);

        $this->menuFrontMatterEvaluator($pageViews);

        $this->mm->buildFromPageViews($pageViews);
        $menu = $this->mm->getSiteMenu();

        $this->assertCount(2, $menu);
        $this->assertNotContains('no', $menu);
    }

    public function testSiteMenuWithEmptyPermalink()
    {
        $pageViews = [];
        $pageViews[] = $this->createFrontMatterDocumentOfType(StaticPageView::class, null, ['permalink' => '/']);

        $this->menuFrontMatterEvaluator($pageViews);

        $this->mm->buildFromPageViews($pageViews);
        $menu = $this->mm->getSiteMenu();

        // @TODO in the next breaking release when the homepage is indexed, change the expected value to 1
        $this->assertCount(0, $menu);
    }

    public function testSiteMenuWithChildrenOutOfOrder()
    {
        $pageViews = [];
        $pageViews[] = $this->createFrontMatterDocumentOfType(StaticPageView::class, null, ['permalink' => '/authors/michael/']);
        $pageViews[] = $this->createFrontMatterDocumentOfType(StaticPageView::class, null, ['permalink' => '/authors/jim/']);
        $pageViews[] = $this->createFrontMatterDocumentOfType(StaticPageView::class, null, ['permalink' => '/authors/']);

        $this->menuFrontMatterEvaluator($pageViews);

        $this->mm->buildFromPageViews($pageViews);
        $menu = $this->mm->getSiteMenu();

        $this->assertCount(1, $menu);
        $this->assertArrayHasKey('authors', $menu);
        $this->assertInstanceOf(JailedDocument::class, $menu['authors']);
        $this->assertCount(2, $menu['authors']->getChildren());
    }
}
