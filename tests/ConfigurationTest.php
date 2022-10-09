<?php declare(strict_types=1);

/**
 * @copyright 2018 Vladimir Jimenez
 * @license   https://github.com/stakx-io/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Test;

use allejo\stakx\Configuration;
use allejo\stakx\Filesystem\File;
use allejo\stakx\Service;
use org\bovigo\vfs\vfsStream;

/**
 * @internal
 *
 * @covers \allejo\stakx\Configuration
 */
class ConfigurationTest extends StakxTestCase
{
    private Configuration $sampleConfig;

    private Configuration $defaultConfig;

    public function setup(): void
    {
        parent::setUp();

        $sampleConfigContent = file_get_contents(__DIR__ . '/assets/ConfigurationFiles/sample.yml');

        $file = $this->createVirtualFile('_config.yml', $sampleConfigContent);

        Service::setWorkingDirectory('vfs://root');

        $this->sampleConfig = new Configuration($this->getMockEventDispatcher(), $this->getMockLogger());
        $this->sampleConfig->parse(new File($file));

        $this->defaultConfig = new Configuration($this->getMockEventDispatcher(), $this->getMockLogger());
        $this->defaultConfig->parse();

        $this->createAssetFolder('ConfigurationTestAssets');
    }

    public function tearDown(): void
    {
        parent::tearDown();

        StreamInterceptor::$output = '';
    }

    public function testSampleConfigIsDebug(): void
    {
        $this->assertTrue($this->sampleConfig->isDebug());
    }

    public function testSampleConfigGetBaseUrl(): void
    {
        $this->assertEquals('super-toast', $this->sampleConfig->getBaseUrl());
    }

    public function testSampleConfigGetDataFolders(): void
    {
        $this->assertCount(1, $this->sampleConfig->getDataFolders());
        $this->assertEquals(['_data'], $this->sampleConfig->getDataFolders());
    }

    public function testSampleConfigGetDataSets(): void
    {
        $this->assertCount(1, $this->sampleConfig->getDataSets());
        $this->assertEquals([
            [
                'name' => 'months',
                'folder' => '_datasets/months',
            ],
        ], $this->sampleConfig->getDataSets());
    }

    public function testSampleConfigCombinedInclude(): void
    {
        $this->assertContains('.htaccess', $this->sampleConfig->getIncludes());
        $this->assertContains('include.html', $this->sampleConfig->getIncludes());
    }

    public function testSampleConfigCombinedExclude(): void
    {
        $this->assertContains('node_modules/', $this->sampleConfig->getExcludes());
        $this->assertContains('exclude.html', $this->sampleConfig->getExcludes());
    }

    public function testSampleConfigGetTheme(): void
    {
        $this->assertEquals('bootstrap', $this->sampleConfig->getTheme());
    }

    public function testSampleConfigGetCustomOptions(): void
    {
        $options = $this->sampleConfig->getConfiguration();

        $this->assertEquals('Sample Title', $options['title']);
        $this->assertEquals('http://facebook.com', $options['social']['facebook']);
        $this->assertEquals('http://twitter.com', $options['social']['twitter']);
    }

    public function testSampleConfigGetPageViews(): void
    {
        $this->assertCount(3, $this->sampleConfig->getPageViewFolders());
        $this->assertContains('_pages', $this->sampleConfig->getPageViewFolders());
        $this->assertContains('_foo', $this->sampleConfig->getPageViewFolders());
    }

    public function testSampleConfigGetTargetFolder(): void
    {
        $this->assertEquals('_bacon/', $this->sampleConfig->getTargetFolder());
    }

    public function testSampleConfigGetCollectionsFolders(): void
    {
        $collections = $this->sampleConfig->getCollectionsFolders();

        $this->assertCount(2, $collections);
        $this->assertContains([
            'name' => 'posts',
            'folder' => '_posts',
        ], $collections);
    }

    public function testDefaultConfigTwigEscape(): void
    {
        $this->assertFalse($this->defaultConfig->getTwigAutoescape());
    }

    public function testDefaultConfigBaseUrl(): void
    {
        $this->assertEmpty($this->defaultConfig->getBaseUrl());
    }

    public function testDefaultConfigTargetUrl(): void
    {
        $this->assertEquals('_site/', $this->defaultConfig->getTargetFolder());
    }

    public function testInvalidConfigurationFails(): void
    {
        $configPath = $this->createVirtualFile('_config.yml', "foo: bar\nfoo baz");

        $config = new Configuration($this->getMockEventDispatcher(), $this->getReadableLogger());
        $config->parse(new File($configPath));

        $this->assertStringContains('parsing failed...', StreamInterceptor::$output);
    }

    public static function provideConfigurationImportsCases(): iterable
    {
        return [
            [
                [
                    'files' => [
                        'parent.yml' => '
value_one: 1
value_two: 2

import:
  - child.yml
',
                        'child.yml' => '
value_one: 5
',
                    ],
                    'keys' => [
                        'value_one' => 5,
                    ],
                ],
            ],

            [
                [
                    'files' => [
                        'child.yml' => '
import:
  - parent.yml

value_one: 1
value_two: 2
',
                        'parent.yml' => '
value_one: 5
',
                    ],
                    'keys' => [
                        'value_one' => 1,
                    ],
                ],
            ],

            [
                [
                    'files' => [
                        'parent.yml' => '
import:
  - child.yml

data:
  - _data
',
                        'child.yml' => '
data:
  - _calendar
',
                    ],
                    'keys' => [
                        'data' => ['_calendar', '_data'],
                    ],
                ],
            ],

            [
                [
                    'files' => [
                        'parent.yml' => '
data:
  - _data

import:
  - child.yml
',
                        'child.yml' => '
data:
  - _calendar
',
                    ],
                    'keys' => [
                        'data' => ['_data', '_calendar'],
                    ],
                ],
            ],

            [
                [
                    'files' => [
                        'parent.yml' => '
data:
  - _data

import:
  - child.yml

value_one: 100
',
                        'child.yml' => '
data:
  - _calendar

value_one: 1
',
                    ],
                    'keys' => [
                        'value_one' => 100,
                    ],
                ],
            ],
        ];
    }

    /**
     * @dataProvider provideConfigurationImportsCases
     */
    public function testConfigurationImports(array $rules): void
    {
        $files = [];

        foreach ($rules['files'] as $fileName => $fileContent) {
            $files[] = $this->createVirtualFile($fileName, $fileContent);
        }

        $masterConfig = $files[0];

        $config = new Configuration($this->getMockEventDispatcher(), $this->getMockLogger());
        $config->parse(new File($masterConfig));
        $result = $config->getConfiguration();

        $this->assertArrayNotHasKey('import', $result);

        foreach ($rules['keys'] as $key => $expectedValue) {
            $this->assertEquals($expectedValue, $result[$key]);
        }
    }

    public function testConfigurationImportDirectoryFails(): void
    {
        $masterConfig = $this->createVirtualFile('_config.yml', "import:\n  - hello/");
        vfsStream::create([
            'hello' => [
                'world.yml' => 'dummy file',
            ],
        ]);

        $config = new Configuration($this->getMockEventDispatcher(), $this->getReadableLogger());
        $config->parse(new File($masterConfig));

        $this->assertStringContains("can't import a directory", StreamInterceptor::$output);
    }

    public function testConfigurationImportSelfFails(): void
    {
        $masterConfig = $this->createVirtualFile('_config.yml', "import:\n  - _config.yml");

        $config = new Configuration($this->getMockEventDispatcher(), $this->getReadableLogger());
        $config->parse(new File($masterConfig));

        $this->assertStringContains("can't import yourself", StreamInterceptor::$output);
    }

    public function testConfigurationImportNonYamlFails(): void
    {
        $masterConfig = $this->createVirtualFile('_config.yml', "import:\n  - my_xml.xml");
        $this->createVirtualFile('my_xml.xml', '<root></root>');

        $config = new Configuration($this->getMockEventDispatcher(), $this->getReadableLogger());
        $config->parse(new File($masterConfig));

        $this->assertStringContains('a non-YAML configuration', StreamInterceptor::$output);
    }

    public function testConfigurationImportSymlinkFails(): never
    {
        $this->markTestIncomplete('This needs to be enabled/changed when I test against a sample site and not vfs.');

        $masterConfig = $this->createVirtualFile('_config.yml', "import:\n  - my_sym.yml");
        $this->createPhysicalFile('original_file.yml', 'value: one');
        $this->fs->symlink(
            $this->fs->appendPath($this->assetFolder, 'original_file.yml'),
            $this->fs->appendPath($this->assetFolder, 'my_sym.yml')
        );

        $config = new Configuration($this->getMockEventDispatcher(), $this->getReadableLogger());
        $config->parse(new File($masterConfig));

        $this->assertStringContains('a symbolically linked file', StreamInterceptor::$output);
    }

    public function testConfigurationImportFileNotFound(): void
    {
        $masterConfig = $this->createVirtualFile('_config.yml', "import:\n  - fake_file.yml");

        $config = new Configuration($this->getMockEventDispatcher(), $this->getReadableLogger());
        $config->parse(new File($masterConfig));

        $this->assertStringContains('could not find file to import', StreamInterceptor::$output);
    }

    public function testConfigurationImportGrandchild(): void
    {
        $masterConfig = $this->createVirtualFile('grandparent.yml', "import:\n  - parent.yml\ngrandparent_value: 1");
        $this->createVirtualFile('parent.yml', "import:\n  - child.yml\nparent_value: 2");
        $this->createVirtualFile('child.yml', 'child_value: 3');

        $config = new Configuration($this->getMockEventDispatcher(), $this->getMockLogger());
        $config->parse(new File($masterConfig));
        $result = $config->getConfiguration();

        $this->assertArrayHasKey('grandparent_value', $result);
        $this->assertArrayHasKey('parent_value', $result);
        $this->assertArrayHasKey('child_value', $result);
    }

    public function testConfigurationImportRecursivelyFails(): void
    {
        $masterConfig = $this->createVirtualFile('_config.yml', "import:\n  - _second.yml");
        $this->createVirtualFile('_second.yml', "import:\n  - _config.yml");

        $config = new Configuration($this->getMockEventDispatcher(), $this->getReadableLogger());
        $config->parse(new File($masterConfig));

        $this->assertStringContains("can't recursively import a file", StreamInterceptor::$output);
    }

    public static function provideConfigurationInvalidImportArrayFailsCases(): iterable
    {
        return [
            ["import:\n- 1"],
            ["import:\n- true"],
            ["import:\n- 2017-01-01"],
        ];
    }

    /**
     * @dataProvider provideConfigurationInvalidImportArrayFailsCases
     *
     * @param string $invalidImportArray
     */
    public function testConfigurationInvalidImportArrayFails($invalidImportArray): void
    {
        $configPath = $this->createVirtualFile('config.yml', $invalidImportArray);

        $config = new Configuration($this->getMockEventDispatcher(), $this->getReadableLogger());
        $config->parse(new File($configPath));

        $this->assertStringContains('invalid import:', StreamInterceptor::$output);
    }

    public static function provideConfigurationImportNotAnArrayCases(): iterable
    {
        return [
            ['import: true'],
            ['import: 1234'],
            ['import: butter & toast'],
        ];
    }

    /**
     * @dataProvider provideConfigurationImportNotAnArrayCases
     *
     * @param string $invalidImport
     */
    public function testConfigurationImportNotAnArray($invalidImport): void
    {
        $configPath = $this->createVirtualFile('config.yml', $invalidImport);

        $config = new Configuration($this->getMockEventDispatcher(), $this->getReadableLogger());
        $config->parse(new File($configPath));

        $this->assertStringContains('the reserved "import" keyword can only be an array', StreamInterceptor::$output);
    }
}
