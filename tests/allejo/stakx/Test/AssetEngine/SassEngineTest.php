<?php

/**
 * @copyright 2018 Vladimir Jimenez
 * @license   https://github.com/stakx-io/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Test\AssetEngine;

use allejo\stakx\AssetEngine\AssetEngineManager;
use allejo\stakx\AssetEngine\SassEngine;
use allejo\stakx\Configuration;
use allejo\stakx\Event\ConfigurationParseComplete;
use allejo\stakx\EventSubscriber\AssetEngineSubscriber;
use allejo\stakx\Test\PHPUnit_Stakx_TestCase;
use Leafo\ScssPhp\Compiler as SassCompiler;

class SassEngineTest extends PHPUnit_Stakx_TestCase
{
    /** @var SassEngine */
    private $sassEngine;

    /** @var AssetEngineManager */
    private $manager;

    public function setUp()
    {
        $this->sassEngine = new SassEngine();
        $this->manager = new AssetEngineManager();

        $this->manager->addAssetEngine($this->sassEngine);
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
        $sass = <<<'SASS'
// A comment
$color: red;

.text-#{$color} {
    color: $color;
}
SASS;

        $settings = [
            'scss' => [
                'style' => $outputFormat,
            ],
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

        $event = new ConfigurationParseComplete($config);
        $subscriber = new AssetEngineSubscriber($this->manager, $this->getMockLogger());
        $subscriber->processConfigurationSettings($event);

        $actualEngine = $this->manager->getEngineByExtension('scss');
        $expectedEngine = new SassCompiler();
        $expectedEngine->setFormatter(SassEngine::stringToFormatter($outputFormat));

        $this->assertNotEquals($expectedEngine, $actualEngine);
        $this->assertEquals($expectedEngine->compile($sass), $actualEngine->parse($sass));
    }
}
