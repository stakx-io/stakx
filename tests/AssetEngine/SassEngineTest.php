<?php

/**
 * @copyright 2018 Vladimir Jimenez
 * @license   https://github.com/stakx-io/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Test\AssetEngine;

use allejo\stakx\AssetEngine\AssetEngineManager;
use allejo\stakx\AssetEngine\Sass\SassEngine;
use allejo\stakx\Compilation\Configuration;
use allejo\stakx\Document\BasePageView;
use allejo\stakx\Document\StaticPageView;
use allejo\stakx\Event\CompilerPostRenderStaticPageView;
use allejo\stakx\Event\ConfigurationParseComplete;
use allejo\stakx\Event\PageManagerPostProcess;
use allejo\stakx\EventSubscriber\AssetEngineSubscriber;
use allejo\stakx\Filesystem\FilesystemLoader as fs;
use allejo\stakx\Manager\PageManager;
use allejo\stakx\Service;
use allejo\stakx\Test\PHPUnit_Stakx_TestCase;
use ScssPhp\ScssPhp\Compiler as SassCompiler;
use org\bovigo\vfs\vfsStream;

class SassEngineTest extends PHPUnit_Stakx_TestCase
{
    /** @var SassEngine */
    private $sassEngine;

    /** @var AssetEngineManager */
    private $manager;

    private $sass = <<<'SASS'
// A comment
$color: red;

.text-#{$color} {
    color: $color;
}
SASS;

    public function setUp()
    {
        parent::setUp();

        $this->sassEngine = new SassEngine($this->getMockConfiguration());

        $this->manager = new AssetEngineManager();
        $this->manager->addAssetEngine($this->sassEngine);
    }

    /**
     * @param array $options
     *
     * @return Configuration|\PHPUnit_Framework_MockObject_MockObject
     */
    private function mockConfigurationGenerator(array $options)
    {
        $settings = [
            'scss' => $options,
        ];

        /** @var Configuration|\PHPUnit_Framework_MockObject_MockObject $config */
        $config = $this->getMockBuilder(Configuration::class)
            ->disableOriginalConstructor()
            ->getMock()
        ;
        $config
            ->method('getConfiguration')
            ->willReturn($settings)
        ;
        $config
            ->method('getTargetFolder')
            ->willReturn('_site')
        ;

        return $config;
    }

    public static function dataProvider_outputStyles()
    {
        return [
            ['nested'],
            ['expanded'],
            ['compact'],
            ['compressed'],
        ];
    }

    /**
     * @dataProvider dataProvider_outputStyles
     *
     * @param string $outputFormat
     */
    public function testSassOutputStyleFromConfiguration($outputFormat)
    {
        $config = $this->mockConfigurationGenerator([
            'style' => $outputFormat,
        ]);

        $event = new ConfigurationParseComplete($config);
        $subscriber = new AssetEngineSubscriber($this->manager, $this->getMockLogger());
        $subscriber->processConfigurationSettings($event);

        $actualEngine = $this->manager->getEngineByExtension('scss');
        $expectedEngine = new SassCompiler();
        $expectedEngine->setFormatter(SassEngine::stringToFormatter($outputFormat));

        $this->assertNotEquals($expectedEngine, $actualEngine);
        $this->assertEquals($expectedEngine->compile($this->sass), $actualEngine->parse($this->sass));
    }

    public function testSassDisabledSourceMap()
    {
        $config = $this->mockConfigurationGenerator([
            'sourcemap' => false,
        ]);

        $event = new ConfigurationParseComplete($config);
        $subscriber = new AssetEngineSubscriber($this->manager, $this->getMockLogger());
        $subscriber->processConfigurationSettings($event);

        $actualEngine = $this->manager->getEngineByExtension('scss');
        $contents = $actualEngine->parse($this->sass);

        $this->assertNotContains('/*# sourceMappingURL=data:application/json', $contents);
    }

    public function testSassInlineSourceMap()
    {
        $config = $this->mockConfigurationGenerator([
            'sourcemap' => 'inline',
        ]);

        $event = new ConfigurationParseComplete($config);
        $subscriber = new AssetEngineSubscriber($this->manager, $this->getMockLogger());
        $subscriber->processConfigurationSettings($event);

        $actualEngine = $this->manager->getEngineByExtension('scss');
        $contents = $actualEngine->parse($this->sass);

        $this->assertContains('/*# sourceMappingURL=data:application/json', $contents);
    }

    public function testSassExternalSourceMap()
    {
        Service::setWorkingDirectory($this->rootDir->url());
        vfsStream::create([
            '.stakx-cache' => [],
            '_sass' => [
                'styles.scss.twig' => $this->buildFrontMatterTemplate(['permalink' => '/styles.css'], $this->sass),
            ],
        ], $this->rootDir);

        $config = $this->mockConfigurationGenerator([
            'sourcemap' => true,
        ]);

        $sassEngine = new SassEngine($config);
        $this->manager->addAssetEngine($sassEngine);

        $pageManager = new PageManager(
            $config,
            $this->getMockCollectionManager(),
            $this->getMockDataManager(),
            $this->getMockAssetManager(),
            $this->getMockEventDistpatcher(),
            $this->getMockLogger()
        );

        $subscriber = new AssetEngineSubscriber($this->manager, $this->getMockLogger());

        $configEvent = new ConfigurationParseComplete($config);
        $subscriber->processConfigurationSettings($configEvent);

        $pageManagerEvent = new PageManagerPostProcess($pageManager);
        $subscriber->processAssetEnginePageView($pageManagerEvent);

        $pageViews = &$pageManager->getPageViews();

        // Dispatch the compiler event, which triggers the sourcemap to be written
        foreach ($pageViews[BasePageView::STATIC_TYPE] as $pageView)
        {
            $compileEvent = new CompilerPostRenderStaticPageView($pageView, $this->sass);
            $subscriber->compileAssetEnginePageViews($compileEvent);
        }

        $expectedFiles = ['styles.css', 'styles.css.map'];
        $actualFileCount = 0;

        /** @var StaticPageView $pageView */
        foreach ($pageViews[BasePageView::STATIC_TYPE] as $pageView)
        {
            $filename = fs::getFilename($pageView->getTargetFile());

            if (in_array($filename, $expectedFiles))
            {
                ++$actualFileCount;
            }
        }

        $this->assertEquals(count($expectedFiles), $actualFileCount);
    }
}
