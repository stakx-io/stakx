<?php

/**
 * @copyright 2018 Vladimir Jimenez
 * @license   https://github.com/stakx-io/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Test;

use allejo\stakx\Compiler;
use allejo\stakx\Filesystem\WritableFolder;
use allejo\stakx\Manager\PageManager;
use allejo\stakx\Templating\Twig\TwigStakxBridgeFactory;
use org\bovigo\vfs\vfsStream;

class CompilerTest extends PHPUnit_Stakx_TestCase
{
    public static function dataProvider_StaticAndRepeaterPageViews()
    {
        return [
            // Static page with single explicit URL with custom extension
            [
                'filename' => 'static.html.twig',
                'frontmatter' => [
                    'permalink' => 'toast.json',
                ],
                'fileContents' => '{}',
                'expectedFiles' => [
                    '/toast.json',
                ],
                'expectedBody' => [
                    '{}',
                ],
            ],

            // Static page with single explicit URL with extension
            [
                'filename' => 'static.html.twig',
                'frontmatter' => [
                    'permalink' => 'toast.html',
                ],
                'fileContents' => 'Page body',
                'expectedFiles' => [
                    '/toast.html',
                ],
                'expectedBody' => [
                    'Page body',
                ],
            ],

            // Static page with single explicit URL without a trailing slash means a file without an extension
            [
                'filename' => 'static.html.twig',
                'frontmatter' => [
                    'permalink' => '/tester',
                ],
                'fileContents' => 'Page body',
                'expectedFiles' => [
                    '/tester',
                ],
                'expectedBody' => [
                    'Page body',
                ],
            ],

            // Static page with single explicit URL with trailing slash means folder with index.html
            [
                'filename' => 'static.html.twig',
                'frontmatter' => [
                    'permalink' => '/tester/',
                ],
                'fileContents' => 'Page body',
                'expectedFiles' => [
                    '/tester/index.html',
                ],
                'expectedBody' => [
                    'Page body',
                ],
            ],

            // Static page with single permalink down multiple directories with an extension
            [
                'filename' => 'static.html.twig',
                'frontmatter' => [
                    'permalink' => '/parent/child/toast.html',
                ],
                'fileContents' => 'Page body',
                'expectedFiles' => [
                    '/parent/child/toast.html',
                ],
                'expectedBody' => [
                    'Page body',
                ],
            ],

            // Static page with single permalink down multiple directories with a trailing slash
            [
                'filename' => 'static.html.twig',
                'frontmatter' => [
                    'permalink' => '/parent/child/toast/',
                ],
                'fileContents' => 'Page body',
                'expectedFiles' => [
                    '/parent/child/toast/index.html',
                ],
                'expectedBody' => [
                    'Page body',
                ],
            ],

            // Static page with redirects
            [
                'filename' => 'static.html.twig',
                'frontmatter' => [
                    'permalink' => [
                        'toast.html',
                        '/toast/',
                    ],
                ],
                'fileContents' => 'Page body',
                'expectedFiles' => [
                    '/toast.html',
                    '/toast/index.html',
                ],
                'expectedBody' => [
                    'Page body',
                    "0;URL='/toast.html'",
                ],
            ],

            // Static page with periods in the permalink
            [
                'filename' => 'static.html.twig',
                'frontmatter' => [
                    'permalink' => '/release-0.1.0-name/',
                ],
                'fileContents' => 'Page body',
                'expectedFiles' => [
                    '/release-0.1.0-name/index.html',
                ],
                'expectedBody' => [
                    'Page body',
                ],
            ],

            // Static page with strange symbols needing to be sanitized in the permalink
            [
                'filename' => 'static.html.twig',
                'frontmatter' => [
                    'permalink' => '/permal:;nk-~!a@^$-w3*rd-c(#4r$/',
                ],
                'fileContents' => 'Page body',
                'expectedFiles' => [
                    '/permalnk-a-w3rd-c4r/index.html',
                ],
                'expectedBody' => [
                    'Page body',
                ],
            ],

            // Repeater page with single expansion
            [
                'filename' => 'static.html.twig',
                'frontmatter' => [
                    'value' => ['one', 'two', 'three', 'four'],
                    'permalink' => '/expandable/%value/',
                ],
                'fileContents' => 'item: {{ this.iterators.value }}',
                'expectedFiles' => [
                    '/expandable/one/index.html',
                    '/expandable/two/index.html',
                    '/expandable/three/index.html',
                    '/expandable/four/index.html',
                ],
                'expectedBody' => [
                    'item: one',
                    'item: two',
                    'item: three',
                    'item: four',
                ],
            ],

            // Repeater page with multiple expansions
            [
                'filename' => 'static.html.twig',
                'frontmatter' => [
                    'category' => ['rants', 'misc', 'toast'],
                    'year' => [2015, 2016, 2017],
                    'permalink' => '/multi-expansion/%year-%category/',
                ],
                'fileContents' => 'cat: {{ this.iterators.category }}; year: {{ this.iterators.year }}',
                'expectedFiles' => [
                    '/multi-expansion/2015-rants/index.html',
                    '/multi-expansion/2015-misc/index.html',
                    '/multi-expansion/2015-toast/index.html',
                    '/multi-expansion/2016-rants/index.html',
                    '/multi-expansion/2016-misc/index.html',
                    '/multi-expansion/2016-toast/index.html',
                    '/multi-expansion/2017-rants/index.html',
                    '/multi-expansion/2017-misc/index.html',
                    '/multi-expansion/2017-toast/index.html',
                ],
                'expectedBody' => [
                    'cat: rants; year: 2015',
                    'cat: misc; year: 2015',
                    'cat: toast; year: 2015',
                    'cat: rants; year: 2016',
                    'cat: misc; year: 2016',
                    'cat: toast; year: 2016',
                    'cat: rants; year: 2017',
                    'cat: misc; year: 2017',
                    'cat: toast; year: 2017',
                ],
            ],

            // Repeater page with single expansion and redirects
            [
                'filename' => 'static.html.twig',
                'frontmatter' => [
                    'month' => ['jan', 'feb', 'mar', 'apr'],
                    'permalink' => [
                        '/calendar/%month/',
                        '/cal/%month/',
                    ],
                ],
                'fileContents' => 'month: {{ this.iterators.month }}',
                'expectedFiles' => [
                    '/calendar/jan/index.html',
                    '/cal/jan/index.html',
                    '/calendar/feb/index.html',
                    '/cal/feb/index.html',
                    '/calendar/mar/index.html',
                    '/cal/mar/index.html',
                    '/calendar/apr/index.html',
                    '/cal/apr/index.html',
                ],
                'expectedBody' => [
                    'month: jan',
                    "0;URL='/calendar/jan/'",
                    'month: feb',
                    "0;URL='/calendar/feb/'",
                    'month: mar',
                    "0;URL='/calendar/mar/'",
                    'month: apr',
                    "0;URL='/calendar/apr/'",
                ],
            ],
        ];
    }

    /**
     * @dataProvider dataProvider_StaticAndRepeaterPageViews
     *
     * @param mixed $filename
     * @param mixed $frontmatter
     * @param mixed $fileContents
     * @param mixed $expectedFiles
     * @param mixed $expectedBody
     */
    public function testCompileStaticAndRepeaterPageViews($filename, $frontmatter, $fileContents, $expectedFiles, $expectedBody)
    {
        vfsStream::create([
            '_pages' => [
                $filename => $this->buildFrontMatterTemplate($frontmatter, $fileContents),
            ],
            '_site' => [],
        ]);

        $pageManager = new PageManager(
            $this->getMockConfiguration(),
            $this->getMockCollectionManager(),
            $this->getMockDataManager(),
            $this->getMockEventDistpatcher(),
            $this->getMockLogger()
        );
        $pageManager->parsePageViews([
            $this->rootDir->getChild('_pages')->url(),
        ]);

        $twigBridge = TwigStakxBridgeFactory::createTwigEnvironment(
            $this->getMockConfiguration(),
            $this->getMockTwigExtension(),
            $this->getMockLogger()
        );

        $folder = new WritableFolder($this->rootDir->getChild('_site')->url());

        $compiler = new Compiler(
            $twigBridge,
            $this->getMockConfiguration(),
            $this->getMockCollectionManager(),
            $this->getMockDataManager(),
            $this->getMockMenuManager(),
            $pageManager,
            $this->getMockEventDistpatcher(),
            $this->getMockLogger()
        );
        $compiler->setTargetFolder($folder);
        $compiler->compileAll();

        foreach ($expectedFiles as $key => $expectedFile)
        {
            $uri = vfsStream::url('root/_site' . $expectedFile);

            $this->assertFileExists($uri);
            $this->assertFileContains($expectedBody[$key], $uri);
        }
    }
}
