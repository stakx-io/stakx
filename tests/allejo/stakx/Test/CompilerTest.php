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
use bovigo\vfs\vfsStream;

class CompilerTest extends PHPUnit_Stakx_TestCase
{
    public static function dataProvider_StaticAndRepeaterPageViews()
    {
        return [
            // Static page with single explicit URL with custom extension
            [
                'sourcePages' => [
                    [
                        'filename' => 'static.html.twig',
                        'frontmatter' => [
                            'permalink' => 'toast.json',
                        ],
                        'contents' => '{}',
                    ],
                ],
                'expectedFiles' => [
                    [
                        'path' => '/toast.json',
                        'body' => '{}',
                    ],
                ],
            ],

            // Static page with single explicit URL with extension
            [
                'sourcePages' => [
                    [
                        'filename' => 'static.html.twig',
                        'frontmatter' => [
                            'permalink' => 'toast.html',
                        ],
                        'contents' => 'Page body',
                    ],
                ],
                'expectedFiles'=> [
                    [
                        'path' => '/toast.html',
                        'body' => 'Page body',
                    ]
                ],
            ],

            // Static page with single explicit URL without a trailing slash means a file without an extension
            [
                'sourcePages' => [
                    [
                        'filename' => 'static.html.twig',
                        'frontmatter' => [
                            'permalink' => '/tester',
                        ],
                        'contents' => 'Page body',
                    ]
                ],
                'expectedFiles'=> [
                    [
                        'path' => '/tester',
                        'body' => 'Page body',
                    ]
                ],
            ],

            // Static page with single explicit URL with trailing slash means folder with index.html
            [
                'sourcePages' => [
                    [
                        'filename' => 'static.html.twig',
                        'frontmatter' => [
                            'permalink' => '/tester/',
                        ],
                        'contents' => 'Page body',
                    ],
                ],
                'expectedFiles' => [
                    [
                        'path' => '/tester/index.html',
                        'body' => 'Page body',
                    ]
                ],
            ],

            // Static page with single permalink down multiple directories with an extension
            [
                'sourcePages' => [
                    [
                        'filename' => 'static.html.twig',
                        'frontmatter' => [
                            'permalink' => '/parent/child/toast.html',
                        ],
                        'contents' => 'Page body',
                    ]
                ],
                'expectedFiles' => [
                    [
                        'path' => '/parent/child/toast.html',
                        'body' => 'Page body',
                    ],
                ],
            ],

            // Static page with single permalink down multiple directories with a trailing slash
            [
                'sourcePages' => [
                    [
                        'filename' => 'static.html.twig',
                        'frontmatter' => [
                            'permalink' => '/parent/child/toast/',
                        ],
                        'contents' => 'Page body',
                    ],
                ],
                'expectedFiles' => [
                    [
                        'path' => '/parent/child/toast/index.html',
                        'body' => 'Page body',
                    ]
                ],
            ],

            // Static page with redirects
            [
                'sourcePages' => [
                    [
                        'filename' => 'static.html.twig',
                        'frontmatter' => [
                            'permalink' => [
                                'toast.html',
                                '/toast/',
                            ],
                        ],
                        'contents' => 'Page body',
                    ],
                ],
                'expectedFiles' => [
                    [
                        'path' => '/toast.html',
                        'body' => 'Page body',
                    ],
                    [
                        'path' => '/toast/index.html',
                        'body' => "0;URL='/toast.html'",
                    ],
                ],
            ],

            // Static page with periods in the permalink
            [
                'sourcePages' => [
                    [
                        'filename' => 'static.html.twig',
                        'frontmatter' => [
                            'permalink' => '/release-0.1.0-name/',
                        ],
                        'contents' => 'Page body',
                    ],
                ],
                'expectedFiles' => [
                    [
                        'path' => '/release-0.1.0-name/index.html',
                        'body' => 'Page body',
                    ]
                ],
            ],

            // Static page with strange symbols needing to be sanitized in the permalink
            [
                'sourcePages' => [
                    [
                        'filename' => 'static.html.twig',
                        'frontmatter' => [
                            'permalink' => '/permal:;nk-~!a@^$-w3*rd-c(#4r$/',
                        ],
                        'contents' => 'Page body',
                    ],
                ],
                'expectedFiles' => [
                    [
                        'path' => '/permalnk-a-w3rd-c4r/index.html',
                        'body' => 'Page body',
                    ]
                ],
            ],

            // Repeater page with single expansion
            [
                'sourcePages' => [
                    [
                        'filename' => 'static.html.twig',
                        'frontmatter' => [
                            'value' => ['one', 'two', 'three', 'four'],
                            'permalink' => '/expandable/%value/',
                        ],
                        'contents' => 'item: {{ this.iterators.value }}',
                    ],
                ],
                'expectedFiles' => [
                    [
                        'path' => '/expandable/one/index.html',
                        'body' => 'item: one',
                    ],
                    [
                        'path' => '/expandable/two/index.html',
                        'body' => 'item: two',
                    ],
                    [
                        'path' => '/expandable/three/index.html',
                        'body' => 'item: three',
                    ],
                    [
                        'path' => '/expandable/four/index.html',
                        'body' => 'item: four',
                    ],
                ],
            ],

            // Repeater page with multiple expansions
            [
                'sourcePages' => [
                    [
                        'filename' => 'static.html.twig',
                        'frontmatter' => [
                            'category' => ['rants', 'misc', 'toast'],
                            'year' => [2015, 2016, 2017],
                            'permalink' => '/multi-expansion/%year-%category/',
                        ],
                        'contents' => 'cat: {{ this.iterators.category }}; year: {{ this.iterators.year }}',
                    ],
                ],
                'expectedFiles' => [
                    [
                        'path' => '/multi-expansion/2015-rants/index.html',
                        'body' => 'cat: rants; year: 2015',
                    ],
                    [
                        'path' => '/multi-expansion/2015-misc/index.html',
                        'body' => 'cat: misc; year: 2015',
                    ],
                    [
                        'path' => '/multi-expansion/2015-toast/index.html',
                        'body' => 'cat: toast; year: 2015',
                    ],
                    [
                        'path' => '/multi-expansion/2016-rants/index.html',
                        'body' => 'cat: rants; year: 2016',
                    ],
                    [
                        'path' => '/multi-expansion/2016-misc/index.html',
                        'body' => 'cat: misc; year: 2016',
                    ],
                    [
                        'path' => '/multi-expansion/2016-toast/index.html',
                        'body' => 'cat: toast; year: 2016',
                    ],
                    [
                        'path' => '/multi-expansion/2017-rants/index.html',
                        'body' => 'cat: rants; year: 2017',
                    ],
                    [
                        'path' => '/multi-expansion/2017-misc/index.html',
                        'body' => 'cat: misc; year: 2017',
                    ],
                    [
                        'path' => '/multi-expansion/2017-toast/index.html',
                        'body' => 'cat: toast; year: 2017',
                    ],
                ],
            ],

            // Repeater page with single expansion and redirects
            [
                'sourcePages' => [
                    [
                        'filename' => 'static.html.twig',
                        'frontmatter' => [
                            'month' => ['jan', 'feb', 'mar', 'apr'],
                            'permalink' => [
                                '/calendar/%month/',
                                '/cal/%month/',
                            ],
                        ],
                        'contents' => 'month: {{ this.iterators.month }}',
                    ],
                ],
                'expectedFiles' => [
                    [
                        'path' => '/calendar/jan/index.html',
                        'body' => 'month: jan',
                    ],
                    [
                        'path' => '/cal/jan/index.html',
                        'body' => "0;URL='/calendar/jan/'",
                    ],
                    [
                        'path' => '/calendar/feb/index.html',
                        'body' => 'month: feb',
                    ],
                    [
                        'path' => '/cal/feb/index.html',
                        'body' => "0;URL='/calendar/feb/'",
                    ],
                    [
                        'path' => '/calendar/mar/index.html',
                        'body' => 'month: mar',
                    ],
                    [
                        'path' => '/cal/mar/index.html',
                        'body' => "0;URL='/calendar/mar/'",
                    ],
                    [
                        'path' => '/calendar/apr/index.html',
                        'body' => 'month: apr',
                    ],
                    [
                        'path' => '/cal/apr/index.html',
                        'body' => "0;URL='/calendar/apr/'",
                    ],
                ],
            ],
        ];
    }

    /**
     * @dataProvider dataProvider_StaticAndRepeaterPageViews
     *
     * @param array $sourcePages
     * @param array $expectedFiles
     */
    public function testCompileStaticAndRepeaterPageViews(array $sourcePages, array $expectedFiles)
    {
        $filesystem = [
            '_pages' => [],
            '_site' => [],
        ];

        foreach ($sourcePages as $sourceFile)
        {
            $filesystem['_pages'][$sourceFile['filename']] = $this->buildFrontMatterTemplate($sourceFile['frontmatter'], $sourceFile['contents']);
        }

        vfsStream::create($filesystem);

        $pageManager = new PageManager(
            $this->getMockConfiguration(),
            $this->getMockCollectionManager(),
            $this->getMockDataManager(),
            $this->getMockAssetManager(),
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
            $this->getMockRedirectMapper(),
            $this->getMockEventDistpatcher(),
            $this->getMockLogger()
        );
        $compiler->setTargetFolder($folder);
        $compiler->compileAll();

        foreach ($expectedFiles as $expectedFile)
        {
            $uri = vfsStream::url('root/_site' . $expectedFile['path']);

            $this->assertFileExists($uri);
            $this->assertFileContains($expectedFile['body'], $uri);
        }
    }
}
