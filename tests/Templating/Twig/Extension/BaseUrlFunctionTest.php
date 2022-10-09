<?php declare(strict_types=1);

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
use allejo\stakx\Test\StakxTestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

/**
 * @internal
 *
 * @covers \allejo\stakx\Templating\Twig\Extension\BaseUrlFunction
 */
class BaseUrlFunctionTest extends StakxTestCase
{
    private Environment $twig_env;

    public function setUp(): void
    {
        parent::setUp();

        /** @var MarkupEngineManager|MockObject $markupEngine */
        $markupEngine = $this->getMockBuilder(MarkupEngineManager::class)->getMock();

        $extension = new TwigExtension($markupEngine);
        $extension->addFilters([new BaseUrlFunction()]);

        $loader = new FilesystemLoader();
        $this->twig_env = new Environment($loader);
        $this->twig_env->addExtension($extension);
    }

    public static function provideBaseUrlFunctionCases(): iterable
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
            ['/toast/static/', 'toast',
                new StaticPageView(new File(
                    fs::appendPath(self::getTestRoot(), 'assets', 'PageViews', 'static.html.twig')
                )),
            ],
        ];
    }

    /**
     * @dataProvider provideBaseUrlFunctionCases
     */
    public function testBaseUrlFunction(mixed $expected, mixed $base, mixed $assetPath): void
    {
        if ($assetPath instanceof FrontMatterDocument) {
            $assetPath->evaluateFrontMatter();
        }

        $this->twig_env->addGlobal('site', [
            'baseurl' => $base,
        ]);

        $filter = new BaseUrlFunction();
        $url = $filter($this->twig_env, $assetPath);

        $this->assertEquals($expected, $url);
    }

    public function testUrlFilterAsAbsolute(): void
    {
        $this->twig_env->addGlobal('site', [
            'url' => 'http://domain.com/',
        ]);

        $filter = new BaseUrlFunction();
        $url = $filter($this->twig_env, '/path/', true);

        $this->assertEquals('http://domain.com/path/', $url);
    }

    public function testUrlFilterAsAbsoluteWithBaseUrl(): void
    {
        $this->twig_env->addGlobal('site', [
            'url' => 'http://domain.com/',
            'baseurl' => '/blog',
        ]);

        $filter = new BaseUrlFunction();
        $url = $filter($this->twig_env, '/path/', true);

        $this->assertEquals('http://domain.com/blog/path/', $url);
    }

    public function testUrlFilterAsRelativeWithBaseUrl(): void
    {
        $this->twig_env->addGlobal('site', [
            'url' => 'http://domain.com/',
            'baseurl' => '/blog',
        ]);

        $filter = new BaseUrlFunction();
        $url = $filter($this->twig_env, '/path/');

        $this->assertEquals('/blog/path/', $url);
    }

    public function testUrlFilterAsRelativeWithNoBaseUrl(): void
    {
        $this->twig_env->addGlobal('site', [
            'url' => 'http://domain.com/',
        ]);

        $filter = new BaseUrlFunction();
        $url = $filter($this->twig_env, '/path/');

        $this->assertEquals('/path/', $url);
    }

    public function testUrlFilterAsAbsoluteWithoutUrl(): void
    {
        $this->twig_env->addGlobal('site', []);

        $filter = new BaseUrlFunction();
        $url = $filter($this->twig_env, '/hello/');

        $this->assertEquals('/hello/', $url);
    }

    public static function provideUrlFilterWithExternalUrlCases(): iterable
    {
        return [
            ['https://google.com'],
            ['http://wololo.com'],
            ['ftp://192.180.0.1'],
            ['irc://freenode.net#stakx'],
        ];
    }

    /**
     * @dataProvider provideUrlFilterWithExternalUrlCases
     */
    public function testUrlFilterWithExternalUrl(mixed $url): void
    {
        $filter = new BaseUrlFunction();
        $result = $filter($this->twig_env, $url);

        $this->assertEquals($result, $url);
    }
}
