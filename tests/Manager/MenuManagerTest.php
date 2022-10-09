<?php declare(strict_types=1);

/**
 * @copyright 2018 Vladimir Jimenez
 * @license   https://github.com/stakx-io/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Test\Manager;

use allejo\stakx\Document\JailedDocument;
use allejo\stakx\Document\StaticPageView;
use allejo\stakx\Manager\MenuManager;
use allejo\stakx\Manager\PageManager;
use allejo\stakx\Test\StakxTestCase;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * @internal
 *
 * @coversNothing
 */
class MenuManagerTest extends StakxTestCase
{
    private MenuManager $mm;

    public function setUp(): void
    {
        parent::setUp();

        /** @var PageManager|MockObject $pm */
        $pm = $this->getMockBuilder(PageManager::class)
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $this->mm = new MenuManager($pm, $this->getMockEventDispatcher(), $this->getMockLogger());
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

    public function testSiteMenuCount(): void
    {
        $pageViews = $this->dataProvider_SingleLevelMenu();
        $this->mm->buildFromPageViews($pageViews);

        $this->assertCount(is_countable($pageViews) ? count($pageViews) : 0, $this->mm->getSiteMenu());
    }

    public function testSiteMenuIsJailed(): void
    {
        $pageViews = $this->dataProvider_SingleLevelMenu();
        $this->mm->buildFromPageViews($pageViews);
        $menu = $this->mm->getSiteMenu();

        $this->assertInstanceOf(JailedDocument::class, reset($menu));
    }

    public function testSiteMenuWithChildren(): void
    {
        $pageViews = $this->dataProvider_SecondLevelMenu();
        $this->mm->buildFromPageViews($pageViews);
        $menu = $this->mm->getSiteMenu();

        $this->assertCount(2, $menu);
        $this->assertCount(3, $menu['authors']->getChildren());
    }

    public function testSiteMenuWithOrphans(): void
    {
        $pageViews = $this->dataProvider_SecondLevelMenuWithOrphans();
        $this->mm->buildFromPageViews($pageViews);
        $menu = $this->mm->getSiteMenu();

        $this->assertCount(1, $menu);
        $this->assertEquals('blog', array_keys($menu)[0]);
    }

    public function testSiteMenuWithGrandchildren(): void
    {
        $pageViews = $this->dataProvider_ThirdLevelMenu();
        $this->mm->buildFromPageViews($pageViews);
        $menu = $this->mm->getSiteMenu();

        $this->assertCount(2, $menu);
        $this->assertCount(2, $authors = $menu['authors']->getChildren());
        $this->assertCount(0, $authors['british']->getChildren());
        $this->assertCount(3, $authors['english']->getChildren());
    }

    public function testSiteMenuExclusionWithMenuFrontMatter(): void
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

    public function testSiteMenuWithEmptyPermalink(): void
    {
        $pageViews = [];
        $pageViews[] = $this->createFrontMatterDocumentOfType(StaticPageView::class, null, ['permalink' => '/']);

        $this->menuFrontMatterEvaluator($pageViews);

        $this->mm->buildFromPageViews($pageViews);
        $menu = $this->mm->getSiteMenu();

        // @TODO in the next breaking release when the homepage is indexed, change the expected value to 1
        $this->assertCount(0, $menu);
    }

    public function testSiteMenuWithChildrenOutOfOrder(): void
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

    private function menuFrontMatterEvaluator(array &$pageViews): void
    {
        foreach ($pageViews as $pageView) {
            $pageView->evaluateFrontMatter();
        }
    }
}
