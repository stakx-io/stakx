<?php

/**
 * @copyright 2017 Vladimir Jimenez
 * @license   https://github.com/allejo/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Test\Manager;

use allejo\stakx\Manager\MenuManager;
use allejo\stakx\Object\JailObject;
use allejo\stakx\Object\PageView;
use allejo\stakx\Test\PHPUnit_Stakx_TestCase;

class MenuManagerTest extends PHPUnit_Stakx_TestCase
{
    /** @var MenuManager */
    private $mm;

    public function setUp()
    {
        parent::setUp();

        $this->mm = new MenuManager();
        $this->mm->setLogger($this->getMockLogger());
    }

    public function dataProvider_SingleLevelMenu()
    {
        $pageViews = array();
        $pageViews[] = $this->createVirtualFile(PageView::class, array('permalink' => '/about/'));
        $pageViews[] = $this->createVirtualFile(PageView::class, array('permalink' => '/contact/'));
        $pageViews[] = $this->createVirtualFile(PageView::class, array('permalink' => '/blog/'));

        return $pageViews;
    }

    public function dataProvider_SecondLevelMenu()
    {
        $pageViews = array();
        $pageViews[] = $this->createVirtualFile(PageView::class, array('permalink' => '/authors/'));
        $pageViews[] = $this->createVirtualFile(PageView::class, array('permalink' => '/authors/michael/'));
        $pageViews[] = $this->createVirtualFile(PageView::class, array('permalink' => '/authors/jim/'));
        $pageViews[] = $this->createVirtualFile(PageView::class, array('permalink' => '/authors/dwight/'));
        $pageViews[] = $this->createVirtualFile(PageView::class, array('permalink' => '/blog/'));

        return $pageViews;
    }

    public function dataProvider_SecondLevelMenuWithOrphans()
    {
        $pageViews = array();
        $pageViews[] = $this->createVirtualFile(PageView::class, array('permalink' => '/authors/michael/'));
        $pageViews[] = $this->createVirtualFile(PageView::class, array('permalink' => '/authors/jim/'));
        $pageViews[] = $this->createVirtualFile(PageView::class, array('permalink' => '/authors/dwight/'));
        $pageViews[] = $this->createVirtualFile(PageView::class, array('permalink' => '/blog/'));

        return $pageViews;
    }

    public function dataProvider_ThirdLevelMenu()
    {
        $pageViews = array();
        $pageViews[] = $this->createVirtualFile(PageView::class, array('permalink' => '/authors/'));
        $pageViews[] = $this->createVirtualFile(PageView::class, array('permalink' => '/authors/british/'));
        $pageViews[] = $this->createVirtualFile(PageView::class, array('permalink' => '/authors/english/'));
        $pageViews[] = $this->createVirtualFile(PageView::class, array('permalink' => '/authors/english/michael/'));
        $pageViews[] = $this->createVirtualFile(PageView::class, array('permalink' => '/authors/english/jim/'));
        $pageViews[] = $this->createVirtualFile(PageView::class, array('permalink' => '/authors/english/dwight/'));
        $pageViews[] = $this->createVirtualFile(PageView::class, array('permalink' => '/blog/'));

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

        $this->assertInstanceOf(JailObject::class, reset($menu));
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
        $pageViews = array();
        $pageViews[] = $this->createVirtualFile(PageView::class, array('permalink' => '/yes/'));
        $pageViews[] = $this->createVirtualFile(PageView::class, array('permalink' => '/yas/', 'menu' => true));
        $pageViews[] = $this->createVirtualFile(PageView::class, array('permalink' => '/no/', 'menu' => false));

        $this->mm->buildFromPageViews($pageViews);
        $menu = $this->mm->getSiteMenu();

        $this->assertCount(2, $menu);
        $this->assertNotContains('no', $menu);
    }

    public function testSiteMenuWithEmptyPermalink()
    {
        $pageViews = array();
        $pageViews[] = $this->createVirtualFile(PageView::class, array('permalink' => '/'));

        $this->mm->buildFromPageViews($pageViews);
        $menu = $this->mm->getSiteMenu();

        // @TODO in the next breaking release when the homepage is indexed, change the expected value to 1
        $this->assertCount(0, $menu);
    }

    public function testSiteMenuWithChildrenOutOfOrder()
    {
        $pageViews = array();
        $pageViews[] = $this->createVirtualFile(PageView::class, array('permalink' => '/authors/michael/'));
        $pageViews[] = $this->createVirtualFile(PageView::class, array('permalink' => '/authors/jim/'));
        $pageViews[] = $this->createVirtualFile(PageView::class, array('permalink' => '/authors/'));

        $this->mm->buildFromPageViews($pageViews);
        $menu = $this->mm->getSiteMenu();

        $this->assertCount(1, $menu);
        $this->assertArrayHasKey('authors', $menu);
        $this->assertInstanceOf(JailObject::class, $menu['authors']);
        $this->assertCount(2, $menu['authors']->getChildren());
    }
}
