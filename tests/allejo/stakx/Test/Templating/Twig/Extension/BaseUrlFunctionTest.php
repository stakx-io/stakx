<?php

/**
 * @copyright 2018 Vladimir Jimenez
 * @license   https://github.com/stakx-io/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Test\Templating\Twig\Extension;

use allejo\stakx\Document\FrontMatterDocument;
use allejo\stakx\Document\StaticPageView;
use allejo\stakx\Filesystem\File;
use allejo\stakx\Filesystem\FilesystemLoader as fs;
use allejo\stakx\MarkupEngine\MarkupEngineManager;
use allejo\stakx\Templating\Twig\Extension\BaseUrlFunction;
use allejo\stakx\Templating\Twig\TwigExtension;
use allejo\stakx\Test\PHPUnit_Stakx_TestCase;
use PHPUnit_Framework_MockObject_MockObject as MockObject;

class BaseUrlFunctionTest extends PHPUnit_Stakx_TestCase
{
    /** @var \Twig_Environment */
    private $twig_env;

    public function setUp()
    {
        parent::setUp();

        /** @var MockObject|MarkupEngineManager $markupEngine */
        $markupEngine = $this->getMock(MarkupEngineManager::class);

        $extension = new TwigExtension($markupEngine);
        $extension->addFilters([new BaseUrlFunction()]);

        $loader = new \Twig_Loader_Filesystem();
        $this->twig_env = new \Twig_Environment($loader);
        $this->twig_env->addExtension($extension);
    }

    public static function dataProvider()
    {
        return [
            ['/toast/link.html', '/toast/', 'link.html'],
            ['/toast/link.html', '/toast', '/link.html'],
            ['/toast/link.html', '/toast/', '//link.html'],
            ['/toast/link.html', '/toast//', '/link.html'],
            ['/toast/link.html', 'toast', '/link.html'],
            ['/toast/link.html', 'toast/', 'link.html'],
            ['/toast/butter/', 'toast/', 'butter/'],
            ['/toast/butter/', '//toast/', '//butter///'],
            ['/toast/', 'toast', null],
            ['/toast/bacon/', 'toast', [
                'permalink' => 'bacon/',
            ]],
            ['/toast/bacon', 'toast', [
                'permalink' => 'bacon',
            ]],
            ['/toast/', 'toast', [
                'some-key' => 'bacon/',
            ]],
            ['/toast/static/', 'toast', (
                new StaticPageView(new File(
                    fs::appendPath(self::getTestRoot(), 'assets', 'PageViews', 'static.html.twig')
                ))
            )],
        ];
    }

    /**
     * @dataProvider dataProvider
     *
     * @param $expected
     * @param $base
     * @param $assetPath
     */
    public function testBaseUrlFunction($expected, $base, $assetPath)
    {
        if ($assetPath instanceof FrontMatterDocument)
        {
            $assetPath->evaluateFrontMatter();
        }

        $this->twig_env->addGlobal('site', [
            'baseurl' => $base,
        ]);

        $filter = new BaseUrlFunction();
        $url = $filter($this->twig_env, $assetPath);

        $this->assertEquals($expected, $url);
    }

    public function testUrlFilterAsAbsolute()
    {
        $this->twig_env->addGlobal('site', [
            'url' => 'http://domain.com/',
        ]);

        $filter = new BaseUrlFunction();
        $url = $filter($this->twig_env, '/path/', true);

        $this->assertEquals('http://domain.com/path/', $url);
    }

    public function testUrlFilterAsAbsoluteWithBaseUrl()
    {
        $this->twig_env->addGlobal('site', [
            'url' => 'http://domain.com/',
            'baseurl' => '/blog',
        ]);

        $filter = new BaseUrlFunction();
        $url = $filter($this->twig_env, '/path/', true);

        $this->assertEquals('http://domain.com/blog/path/', $url);
    }

    public function testUrlFilterAsRelativeWithBaseUrl()
    {
        $this->twig_env->addGlobal('site', [
            'url' => 'http://domain.com/',
            'baseurl' => '/blog',
        ]);

        $filter = new BaseUrlFunction();
        $url = $filter($this->twig_env, '/path/');

        $this->assertEquals('/blog/path/', $url);
    }

    public function testUrlFilterAsRelativeWithNoBaseUrl()
    {
        $this->twig_env->addGlobal('site', [
            'url' => 'http://domain.com/',
        ]);

        $filter = new BaseUrlFunction();
        $url = $filter($this->twig_env, '/path/');

        $this->assertEquals('/path/', $url);
    }

    public function testUrlFilterAsAbsoluteWithoutUrl()
    {
        $this->twig_env->addGlobal('site', []);

        $filter = new BaseUrlFunction();
        $url = $filter($this->twig_env, '/hello/');

        $this->assertEquals('/hello/', $url);
    }
}
