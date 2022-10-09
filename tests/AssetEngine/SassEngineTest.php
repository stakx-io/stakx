<?php declare(strict_types=1);

/**
 * @copyright 2018 Vladimir Jimenez
 * @license   https://github.com/stakx-io/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Test\AssetEngine;

use allejo\stakx\AssetEngine\AssetEngineManager;
use allejo\stakx\AssetEngine\Sass\SassEngine;
use allejo\stakx\Configuration;
use allejo\stakx\Document\BasePageView;
use allejo\stakx\Document\StaticPageView;
use allejo\stakx\Event\CompilerPostRenderStaticPageView;
use allejo\stakx\Event\ConfigurationParseComplete;
use allejo\stakx\Event\PageManagerPostProcess;
use allejo\stakx\EventSubscriber\AssetEngineSubscriber;
use allejo\stakx\Filesystem\FilesystemLoader as fs;
use allejo\stakx\Manager\PageManager;
use allejo\stakx\Service;
use allejo\stakx\Test\StakxTestCase;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\MockObject\MockObject;
use ScssPhp\ScssPhp\Compiler as SassCompiler;

/**
 * @internal
 *
 * @coversNothing
 */
class SassEngineTest extends StakxTestCase
{
    private SassEngine $sassEngine;

    private AssetEngineManager $manager;

    private string $sass = <<<'SASS'
// A comment
$color: red;

.text-#{$color} {
    color: $color;
}
SASS;

    public function setUp(): void
    {
        parent::setUp();

        $this->sassEngine = new SassEngine($this->getMockConfiguration());

        $this->manager = new AssetEngineManager();
        $this->manager->addAssetEngine($this->sassEngine);
    }

    public static function provideSassOutputStyleFromConfigurationCases(): iterable
    {
        return [
            ['nested'],
            ['expanded'],
            ['compact'],
            ['compressed'],
        ];
    }

    /**
     * @dataProvider provideSassOutputStyleFromConfigurationCases
     *
     * @param string $outputFormat
     */
    public function testSassOutputStyleFromConfiguration($outputFormat): void
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

    public function testSassDisabledSourceMap(): void
    {
        $config = $this->mockConfigurationGenerator([
            'sourcemap' => false,
        ]);

        $event = new ConfigurationParseComplete($config);
        $subscriber = new AssetEngineSubscriber($this->manager, $this->getMockLogger());
        $subscriber->processConfigurationSettings($event);

        $actualEngine = $this->manager->getEngineByExtension('scss');
        $contents = $actualEngine->parse($this->sass);

        $this->assertStringNotContainsString('/*# sourceMappingURL=data:application/json', $contents);
    }

    public function testSassInlineSourceMap(): void
    {
        $config = $this->mockConfigurationGenerator([
            'sourcemap' => 'inline',
        ]);

        $event = new ConfigurationParseComplete($config);
        $subscriber = new AssetEngineSubscriber($this->manager, $this->getMockLogger());
        $subscriber->processConfigurationSettings($event);

        $actualEngine = $this->manager->getEngineByExtension('scss');
        $contents = $actualEngine->parse($this->sass);

        $this->assertStringContains('/*# sourceMappingURL=data:application/json', $contents);
    }

    public function testSassExternalSourceMap(): void
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
            $this->getMockEventDispatcher(),
            $this->getMockLogger()
        );

        $subscriber = new AssetEngineSubscriber($this->manager, $this->getMockLogger());

        $configEvent = new ConfigurationParseComplete($config);
        $subscriber->processConfigurationSettings($configEvent);

        $pageManagerEvent = new PageManagerPostProcess($pageManager);
        $subscriber->processAssetEnginePageView($pageManagerEvent);

        $pageViews = &$pageManager->getPageViews();

        // Dispatch the compiler event, which triggers the sourcemap to be written
        foreach ($pageViews[BasePageView::STATIC_TYPE] as $pageView) {
            $compileEvent = new CompilerPostRenderStaticPageView($pageView, $this->sass);
            $subscriber->compileAssetEnginePageViews($compileEvent);
        }

        $expectedFiles = ['styles.css', 'styles.css.map'];
        $actualFileCount = 0;

        /** @var StaticPageView $pageView */
        foreach ($pageViews[BasePageView::STATIC_TYPE] as $pageView) {
            $filename = fs::getFilename($pageView->getTargetFile());

            if (in_array($filename, $expectedFiles)) {
                ++$actualFileCount;
            }
        }

        $this->assertEquals(count($expectedFiles), $actualFileCount);
    }

    private function mockConfigurationGenerator(array $options): Configuration|MockObject
    {
        $settings = [
            'scss' => $options,
        ];

        /** @var Configuration|MockObject $config */
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
}
