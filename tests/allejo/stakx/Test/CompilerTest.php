<?php

/**
 * @copyright 2017 Vladimir Jimenez
 * @license   https://github.com/allejo/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Test;

use allejo\stakx\Compiler;
use allejo\stakx\Manager\TwigManager;
use allejo\stakx\Object\Configuration;
use allejo\stakx\Object\PageView;
use allejo\stakx\System\Folder;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\visitor\vfsStreamStructureVisitor;

class CompilerTest extends PHPUnit_Stakx_TestCase
{
    /** @var Folder */
    private $folder;

    public function setUp()
    {
        parent::setUp();

        vfsStream::create(array(
            '_site' => array(),
        ));

        $config = new Configuration();
        $config->parseConfiguration();

        $twigEnv = new TwigManager();
        $twigEnv->configureTwig($config, array(
            'safe' => false,
            'globals' => array(),
        ));

        $this->folder = new Folder($this->rootDir->getChild('_site')->url());
    }

    public static function dataProviderStaticPageViews()
    {
        return array(
            array('/toast.html', 'toast.html'),
            array('/hello-world/', 'hello-world/index.html'),
            array('/foo/bar/data.json', 'foo/bar/data.json'),
            array('/static-page', 'static-page'),
            array('/release-0.1.0-name/', 'release-0.1.0-name/index.html'),
        );
    }

    /**
     * @dataProvider dataProviderStaticPageViews
     *
     * @param string $permalink
     * @param string $targetPath
     */
    public function testStaticPageViewWrite($permalink, $targetPath)
    {
        $pageView = $this->createVirtualFile(PageView::class, array('permalink' => $permalink));
        $pageViews = array(
            PageView::STATIC_TYPE => array(&$pageView),
            PageView::DYNAMIC_TYPE => array(),
            PageView::REPEATER_TYPE => array(),
        );
        $pageViewsFlattened = array(&$pageView);

        $compiler = new Compiler();
        $compiler->setLogger($this->getMockLogger());
        $compiler->setTargetFolder($this->folder);
        $compiler->setPageViews($pageViews, $pageViewsFlattened);
        $compiler->compileAll();

        $this->assertFileExists(vfsStream::url('root/_site/' . $targetPath));
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
     * @param array  $permalinks
     */
    public function testStaticPageViewRedirectsWrite(array $permalinks)
    {
        /** @var PageView $pageView */
        $pageView = $this->createVirtualFile(PageView::class, array('permalink' => $permalinks));
        $pageViews = array(
            PageView::STATIC_TYPE => array(&$pageView),
            PageView::DYNAMIC_TYPE => array(),
            PageView::REPEATER_TYPE => array(),
        );
        $pageViewsFlattened = array(&$pageView);

        $compiler = new Compiler();
        $compiler->setLogger($this->getMockLogger());
        $compiler->setTargetFolder($this->folder);
        $compiler->setPageViews($pageViews, $pageViewsFlattened);
        $compiler->compileAll();
        $permalink = array_shift($permalinks);

        $this->assertFileExists(vfsStream::url('root/_site/' . $pageView->getTargetFile()));

        foreach ($permalinks as $redirect)
        {
            $uri = vfsStream::url('root/_site' . $redirect);
            $this->assertFileExists($uri);
            $this->assertFileContains("0;URL='$permalink'", $uri);
        }
    }
}
