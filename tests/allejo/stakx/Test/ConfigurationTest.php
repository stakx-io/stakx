<?php

/**
 * @copyright 2018 Vladimir Jimenez
 * @license   https://github.com/stakx-io/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Test;

use allejo\stakx\Configuration;
use allejo\stakx\Filesystem\File;
use allejo\stakx\Service;
use org\bovigo\vfs\vfsStream;

class ConfigurationTest extends PHPUnit_Stakx_TestCase
{
    /** @var Configuration */
    private $sampleConfig;
    /** @var Configuration */
    private $defaultConfig;

    public function setup()
    {
        parent::setUp();

        $sampleConfigContent = file_get_contents(__DIR__ . '/assets/ConfigurationFiles/sample.yml');

        $file = $this->createVirtualFile('_config.yml', $sampleConfigContent);

        Service::setWorkingDirectory('vfs://root');

        $this->sampleConfig = new Configuration($this->getMockEventDistpatcher(), $this->getMockLogger());
        $this->sampleConfig->parse(new File($file));

        $this->defaultConfig = new Configuration($this->getMockEventDistpatcher(), $this->getMockLogger());
        $this->defaultConfig->parse();

        $this->createAssetFolder('ConfigurationTestAssets');
    }

    public function tearDown()
    {
        parent::tearDown();

        StreamInterceptor::$output = '';
    }

    public function testSampleConfigIsDebug()
    {
        $this->assertTrue($this->sampleConfig->isDebug());
    }

    public function testSampleConfigGetBaseUrl()
    {
        $this->assertEquals('super-toast', $this->sampleConfig->getBaseUrl());
    }

    public function testSampleConfigGetDataFolders()
    {
        $this->assertCount(1, $this->sampleConfig->getDataFolders());
        $this->assertEquals(['_data'], $this->sampleConfig->getDataFolders());
    }

    public function testSampleConfigGetDataSets()
    {
        $this->assertCount(1, $this->sampleConfig->getDataSets());
        $this->assertEquals([
            [
                'name' => 'months',
                'folder' => '_datasets/months',
            ],
        ], $this->sampleConfig->getDataSets());
    }

    public function testSampleConfigCombinedInclude()
    {
        $this->assertContains('.htaccess', $this->sampleConfig->getIncludes());
        $this->assertContains('include.html', $this->sampleConfig->getIncludes());
    }

    public function testSampleConfigCombinedExclude()
    {
        $this->assertContains('node_modules/', $this->sampleConfig->getExcludes());
        $this->assertContains('exclude.html', $this->sampleConfig->getExcludes());
    }

    public function testSampleConfigGetTheme()
    {
        $this->assertEquals('bootstrap', $this->sampleConfig->getTheme());
    }

    public function testSampleConfigGetCustomOptions()
    {
        $options = $this->sampleConfig->getConfiguration();

        $this->assertEquals('Sample Title', $options['title']);
        $this->assertEquals('http://facebook.com', $options['social']['facebook']);
        $this->assertEquals('http://twitter.com', $options['social']['twitter']);
    }

    public function testSampleConfigGetPageViews()
    {
        $this->assertCount(3, $this->sampleConfig->getPageViewFolders());
        $this->assertContains('_pages', $this->sampleConfig->getPageViewFolders());
        $this->assertContains('_foo', $this->sampleConfig->getPageViewFolders());
    }

    public function testSampleConfigGetTargetFolder()
    {
        $this->assertEquals('_bacon/', $this->sampleConfig->getTargetFolder());
    }

    public function testSampleConfigGetCollectionsFolders()
    {
        $collections = $this->sampleConfig->getCollectionsFolders();

        $this->assertCount(2, $collections);
        $this->assertContains([
            'name' => 'posts',
            'folder' => '_posts',
        ], $collections);
    }

    public function testDefaultConfigTwigEscape()
    {
        $this->assertFalse($this->defaultConfig->getTwigAutoescape());
    }

    public function testDefaultConfigBaseUrl()
    {
        $this->assertEmpty($this->defaultConfig->getBaseUrl());
    }

    public function testDefaultConfigTargetUrl()
    {
        $this->assertEquals('_site/', $this->defaultConfig->getTargetFolder());
    }

    public function testInvalidConfigurationFails()
    {
        $configPath = $this->createVirtualFile('_config.yml', "foo: bar\nfoo baz");

        $config = new Configuration($this->getMockEventDistpatcher(), $this->getReadableLogger());
        $config->parse(new File($configPath));

        $this->assertStringContains('parsing failed...', StreamInterceptor::$output);
    }

    public static function dataProviderImportTests()
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
     * @dataProvider dataProviderImportTests
     *
     * @param array $rules
     */
    public function testConfigurationImports(array $rules)
    {
        $files = [];

        foreach ($rules['files'] as $fileName => $fileContent)
        {
            $files[] = $this->createVirtualFile($fileName, $fileContent);
        }

        $masterConfig = $files[0];

        $config = new Configuration($this->getMockEventDistpatcher(), $this->getMockLogger());
        $config->parse(new File($masterConfig));
        $result = $config->getConfiguration();

        $this->assertArrayNotHasKey('import', $result);

        foreach ($rules['keys'] as $key => $expectedValue)
        {
            $this->assertEquals($expectedValue, $result[$key]);
        }
    }

    public function testConfigurationImportDirectoryFails()
    {
        $masterConfig = $this->createVirtualFile('_config.yml', "import:\n  - hello/");
        vfsStream::create([
            'hello' => [
                'world.yml' => 'dummy file',
            ],
        ]);

        $config = new Configuration($this->getMockEventDistpatcher(), $this->getReadableLogger());
        $config->parse(new File($masterConfig));

        $this->assertContains("can't import a directory", StreamInterceptor::$output);
    }

    public function testConfigurationImportSelfFails()
    {
        $masterConfig = $this->createVirtualFile('_config.yml', "import:\n  - _config.yml");

        $config = new Configuration($this->getMockEventDistpatcher(), $this->getReadableLogger());
        $config->parse(new File($masterConfig));

        $this->assertContains("can't import yourself", StreamInterceptor::$output);
    }

    public function testConfigurationImportNonYamlFails()
    {
        $masterConfig = $this->createVirtualFile('_config.yml', "import:\n  - my_xml.xml");
        $this->createVirtualFile('my_xml.xml', '<root></root>');

        $config = new Configuration($this->getMockEventDistpatcher(), $this->getReadableLogger());
        $config->parse(new File($masterConfig));

        $this->assertContains('a non-YAML configuration', StreamInterceptor::$output);
    }

    public function testConfigurationImportSymlinkFails()
    {
        $this->markTestIncomplete('This needs to be enabled/changed when I test against a sample site and not vfs.');

        $masterConfig = $this->createVirtualFile('_config.yml', "import:\n  - my_sym.yml");
        $this->createPhysicalFile('original_file.yml', 'value: one');
        $this->fs->symlink(
            $this->fs->appendPath($this->assetFolder, 'original_file.yml'),
            $this->fs->appendPath($this->assetFolder, 'my_sym.yml')
        );

        $config = new Configuration($this->getMockEventDistpatcher(), $this->getReadableLogger());
        $config->parse(new File($masterConfig));

        $this->assertContains('a symbolically linked file', StreamInterceptor::$output);
    }

    public function testConfigurationImportFileNotFound()
    {
        $masterConfig = $this->createVirtualFile('_config.yml', "import:\n  - fake_file.yml");

        $config = new Configuration($this->getMockEventDistpatcher(), $this->getReadableLogger());
        $config->parse(new File($masterConfig));

        $this->assertContains('could not find file to import', StreamInterceptor::$output);
    }

    public function testConfigurationImportGrandchild()
    {
        $masterConfig = $this->createVirtualFile('grandparent.yml', "import:\n  - parent.yml\ngrandparent_value: 1");
        $this->createVirtualFile('parent.yml', "import:\n  - child.yml\nparent_value: 2");
        $this->createVirtualFile('child.yml', 'child_value: 3');

        $config = new Configuration($this->getMockEventDistpatcher(), $this->getMockLogger());
        $config->parse(new File($masterConfig));
        $result = $config->getConfiguration();

        $this->assertArrayHasKey('grandparent_value', $result);
        $this->assertArrayHasKey('parent_value', $result);
        $this->assertArrayHasKey('child_value', $result);
    }

    public function testConfigurationImportRecursivelyFails()
    {
        $masterConfig = $this->createVirtualFile('_config.yml', "import:\n  - _second.yml");
        $this->createVirtualFile('_second.yml', "import:\n  - _config.yml");

        $config = new Configuration($this->getMockEventDistpatcher(), $this->getReadableLogger());
        $config->parse(new File($masterConfig));

        $this->assertContains("can't recursively import a file", StreamInterceptor::$output);
    }

    public static function dataProviderInvalidImportArrays()
    {
        return [
            ["import:\n- 1"],
            ["import:\n- true"],
            ["import:\n- 2017-01-01"],
        ];
    }

    /**
     * @dataProvider dataProviderInvalidImportArrays
     *
     * @param string $invalidImportArray
     */
    public function testConfigurationInvalidImportArrayFails($invalidImportArray)
    {
        $configPath = $this->createVirtualFile('config.yml', $invalidImportArray);

        $config = new Configuration($this->getMockEventDistpatcher(), $this->getReadableLogger());
        $config->parse(new File($configPath));

        $this->assertContains('invalid import:', StreamInterceptor::$output);
    }

    public static function dataProviderInvalidImports()
    {
        return [
            ['import: true'],
            ['import: 1234'],
            ['import: butter & toast'],
        ];
    }

    /**
     * @dataProvider dataProviderInvalidImports
     *
     * @param string $invalidImport
     */
    public function testConfigurationImportNotAnArray($invalidImport)
    {
        $configPath = $this->createVirtualFile('config.yml', $invalidImport);

        $config = new Configuration($this->getMockEventDistpatcher(), $this->getReadableLogger());
        $config->parse(new File($configPath));

        $this->assertContains('the reserved "import" keyword can only be an array', StreamInterceptor::$output);
    }
}
