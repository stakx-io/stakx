<?php

/**
 * @copyright 2017 Vladimir Jimenez
 * @license   https://github.com/allejo/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Test;

use allejo\stakx\Command\BuildableCommand;
use allejo\stakx\Compiler;
use allejo\stakx\Configuration;
use allejo\stakx\DocumentDeprecated\ContentItem;
use allejo\stakx\DocumentDeprecated\DynamicPageView;
use allejo\stakx\DocumentDeprecated\PageView;
use allejo\stakx\DocumentDeprecated\RepeaterPageView;
use allejo\stakx\Manager\TwigManager;
use allejo\stakx\Service;
use allejo\stakx\Filesystem\Folder;
use allejo\stakx\Templating\TwigStakxBridgeFactory;
use org\bovigo\vfs\vfsStream;

class CompilerTest extends PHPUnit_Stakx_TestCase
{
    /** @var Folder */
    private $folder;

    /** @var Compiler */
    private $compiler;

    public function setUp()
    {
        parent::setUp();

        vfsStream::create(array(
            '_site' => array(),
        ));

        $config = new Configuration();
        $config->setLogger($this->getMockLogger());
        $config->parse();

        $twig = TwigStakxBridgeFactory::createTwigEnvironment($config, $this->getMockLogger());

        $this->folder = new Folder($this->rootDir->getChild('_site')->url());

        $this->compiler = new Compiler($twig);
        $this->compiler->setLogger($this->getMockLogger());
        $this->compiler->setTargetFolder($this->folder);
    }

    public function tearDown()
    {
        parent::tearDown();

        Service::setParameter(BuildableCommand::USE_DRAFTS, false);
    }

    public static function dataProviderStaticPageViewsRedirects()
    {
        return array(
            array(
                array('/bacon/', '/bacon.html', '/baconator'),
            ),
            array(
                array('/hello.html', '/h', '/hello'),
            ),
            array(
                array('/author/scott/', '/scott/', '/scotty.html'),
            ),
            array(
                array('/category/toast/', '/cat/toast/', '/category-toast.html'),
            ),
        );
    }

    /**
     * @dataProvider dataProviderStaticPageViewsRedirects
     *
     * @param array $permalinks
     */
    public function testStaticPageViewRedirectsWrite(array $permalinks)
    {
        /** @var PageView $pageView */
        $pageView = $this->createVirtualFrontMatterFile(PageView::class, array('permalink' => $permalinks));
        $pageViews = array(
            PageView::STATIC_TYPE => array(&$pageView),
            PageView::DYNAMIC_TYPE => array(),
            PageView::REPEATER_TYPE => array(),
        );
        $pageViewsFlattened = array(&$pageView);

        $this->compiler->setPageViews($pageViews, $pageViewsFlattened);
        $this->compiler->compileAll();
        $permalink = array_shift($permalinks);

        $this->assertFileExists(vfsStream::url('root/_site/' . $pageView->getTargetFile()));

        foreach ($permalinks as $redirect)
        {
            $uri = vfsStream::url('root/_site' . $redirect);
            $this->assertFileExists($uri);
            $this->assertFileContains("0;URL='$permalink'", $uri);
        }
    }

    public static function dataProviderPageViewTargetFiles()
    {
        return array(
            // Static PageView tests
            array(
                PageView::class,
                PageView::STATIC_TYPE,
                array('permalink' => '/toast.html'),
                array('/toast.html'),
            ),
            array(
                PageView::class,
                PageView::STATIC_TYPE,
                array('permalink' => '/hello-world/'),
                array('/hello-world/index.html'),
            ),
            array(
                PageView::class,
                PageView::STATIC_TYPE,
                array('permalink' => '/foo/bar/data.json'),
                array('/foo/bar/data.json'),
            ),
            array(
                PageView::class,
                PageView::STATIC_TYPE,
                array('permalink' => '/static-page'),
                array('/static-page'),
            ),
            array(
                PageView::class,
                PageView::STATIC_TYPE,
                array('permalink' => '/release-0.1.0-name/'),
                array('/release-0.1.0-name/index.html'),
            ),
            array(
                PageView::class,
                PageView::STATIC_TYPE,
                array('permalink' => '/permal:;nk-~!a@^$-w3*rd-c(#4r$/'),
                array('/permalnk-a-w3rd-c4r/index.html'),
            ),

            // Repeater PageView tests
            array(
                RepeaterPageView::class,
                PageView::REPEATER_TYPE,
                array(
                    'value' => array('one', 'two', 'three', 'four'),
                    'permalink' => '/expandable/%value/',
                ),
                array(
                    '/expandable/one/index.html',
                    '/expandable/two/index.html',
                    '/expandable/three/index.html',
                    '/expandable/four/index.html',
                ),
            ),
            array(
                RepeaterPageView::class,
                PageView::REPEATER_TYPE,
                array(
                    'category' => array('rants', 'misc', 'toast'),
                    'year' => array(2015, 2016, 2017),
                    'permalink' => '/multi-expansion/%year-%category/',
                ),
                array(
                    '/multi-expansion/2015-rants/index.html',
                    '/multi-expansion/2015-misc/index.html',
                    '/multi-expansion/2015-toast/index.html',
                    '/multi-expansion/2016-rants/index.html',
                    '/multi-expansion/2016-misc/index.html',
                    '/multi-expansion/2016-toast/index.html',
                    '/multi-expansion/2017-rants/index.html',
                    '/multi-expansion/2017-misc/index.html',
                    '/multi-expansion/2017-toast/index.html',
                ),
            ),
        );
    }

    /**
     * @dataProvider dataProviderPageViewTargetFiles
     *
     * @param $class
     * @param $pageViewType
     * @param $frontMatter
     * @param $expectedFiles
     */
    public function testPageViewFileWritesExists($class, $pageViewType, $frontMatter, $expectedFiles)
    {
        /** @var RepeaterPageView $pageView */
        $pageView = $this->createVirtualFrontMatterFile($class, $frontMatter);
        $pageViews = array(
            PageView::STATIC_TYPE => array(),
            PageView::DYNAMIC_TYPE => array(),
            PageView::REPEATER_TYPE => array(),
        );
        $pageViews[$pageViewType][] = &$pageView;
        $pageViewsFlattened = array(&$pageView);

        $this->compiler->setPageViews($pageViews, $pageViewsFlattened);
        $this->compiler->compileAll();

        foreach ($expectedFiles as $expectedFile)
        {
            $uri = vfsStream::url('root/_site' . $expectedFile);
            $this->assertFileExists($uri);
        }
    }

    private function prepareCompilerForDynamicPageViews()
    {
        /** @var ContentItem[][] $books */
        $books = $this->bookCollectionProvider();

        /** @var DynamicPageView $pageView */
        $pageView = $this->createVirtualFrontMatterFile(DynamicPageView::class, array(
            'collction' => 'books',
            'permalink' => '/my-books/%title/',
        ));
        $pageViews = array(
            PageView::STATIC_TYPE => array(),
            PageView::DYNAMIC_TYPE => array(),
            PageView::REPEATER_TYPE => array(),
        );
        $pageViews[PageView::DYNAMIC_TYPE][] = &$pageView;
        $pageViewsFlattened = array(&$pageView);

        foreach ($books['books'] as &$item)
        {
            $item->evaluateFrontMatter($pageView->getFrontMatter(false));
            $pageView->addRepeatableItem($item);
        }

        $this->compiler->setPageViews($pageViews, $pageViewsFlattened);
    }

    public function testDynamicPageViewWriteNonDrafts()
    {
        $this->prepareCompilerForDynamicPageViews();
        $this->compiler->compileAll();

        $expected = array(
            '/my-books/because-of-winn-dixie/index.html',
            '/my-books/go-dog-go/index.html',
            '/my-books/one-fish-two-fish/index.html',
            '/my-books/tale-of-despereaux/index.html',
            '/my-books/tiger-rising/index.html',
        );

        foreach ($expected as $file)
        {
            $uri = vfsStream::url('root/_site' . $file);
            $this->assertFileExists($uri);
        }

        // Our draft post shouldn't be written
        $uri = vfsStream::url('root/_site/my-books/unpublished-title/index.html');
        $this->assertFileNotExists($uri);
    }

    public function testDynamicPageViewWriteDrafts()
    {
        Service::setParameter(BuildableCommand::USE_DRAFTS, true);

        $this->prepareCompilerForDynamicPageViews();
        $this->compiler->compileAll();

        $uri = vfsStream::url('root/_site/my-books/unpublished-title/index.html');
        $this->assertFileExists($uri);
    }
}
