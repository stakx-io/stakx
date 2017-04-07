<?php

/**
 * @copyright 2017 Vladimir Jimenez
 * @license   https://github.com/allejo/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Test;

use allejo\stakx\Configuration;

class ConfigurationTest extends PHPUnit_Stakx_TestCase
{
    /**
     * @var Configuration
     */
    private $sampleConfig;

    /**
     * @var Configuration
     */
    private $defaultConfig;

    public function setup()
    {
        parent::setUp();

        $output = $this->getMockLogger();
        $this->sampleConfig = new Configuration();
        $this->sampleConfig->setLogger($output);
        $this->sampleConfig->parse(__DIR__ . '/assets/ConfigurationFiles/sample.yml');

        $this->defaultConfig = new Configuration();
        $this->defaultConfig->setLogger($output);
        $this->defaultConfig->parse();

        $this->createAssetFolder('ConfigurationTestAssets');
    }

    public function tearDown()
    {
        parent::tearDown();

        StreamInterceptor::$output = '';

        $this->fs->remove($this->assetFolder);
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
        $this->assertEquals(array('_data'), $this->sampleConfig->getDataFolders());
    }

    public function testSampleConfigGetDataSets()
    {
        $this->assertCount(1, $this->sampleConfig->getDataSets());
        $this->assertEquals(array(
            array(
                'name' => 'months',
                'folder' => '_datasets/months',
            ),
        ), $this->sampleConfig->getDataSets());
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
        $this->assertEquals('_bacon', $this->sampleConfig->getTargetFolder());
    }

    public function testSampleConfigGetCollectionsFolders()
    {
        $collections = $this->sampleConfig->getCollectionsFolders();

        $this->assertCount(2, $collections);
        $this->assertContains(array(
            'name' => 'posts',
            'folder' => '_posts',
        ), $collections);
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
        $this->assertEquals('_site', $this->defaultConfig->getTargetFolder());
    }

    public function testInvalidConfigurationFails()
    {
        $output = $this->getReadableLogger();
        $configPath = $this->writeTempFile('_config.yml', "foo: bar\nfoo baz");

        $config = new Configuration();
        $config->setLogger($output);
        $config->parse($configPath);

        $this->assertStringContains('parsing failed...', StreamInterceptor::$output);
    }

    public static function dataProviderImportTests()
    {
        return array(
            array(
                array(
                    'files' => array(
                        'parent.yml' => '
value_one: 1
value_two: 2

import:
  - child.yml
',
                        'child.yml' => '
value_one: 5
',
                    ),
                    'keys' => array(
                        'value_one' => 5,
                    ),
                ),
            ),

            array(
                array(
                    'files' => array(
                        'child.yml' => '
import:
  - parent.yml

value_one: 1
value_two: 2
',
                        'parent.yml' => '
value_one: 5
',
                    ),
                    'keys' => array(
                        'value_one' => 1,
                    ),
                ),
            ),

            array(
                array(
                    'files' => array(
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
                    ),
                    'keys' => array(
                        'data' => array('_calendar', '_data'),
                    ),
                ),
            ),

            array(
                array(
                    'files' => array(
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
                    ),
                    'keys' => array(
                        'data' => array('_data', '_calendar'),
                    ),
                ),
            ),

            array(
                array(
                    'files' => array(
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
                    ),
                    'keys' => array(
                        'value_one' => 100
                    ),
                ),
            ),
        );
    }

    /**
     * @dataProvider dataProviderImportTests
     *
     * @param array $rules
     */
    public function testConfigurationImports(array $rules)
    {
        $files = array();

        foreach ($rules['files'] as $fileName => $fileContent)
        {
            $files[] = $this->writeTempFile($fileName, $fileContent);
        }

        $masterConfig = $files[0];

        $config = new Configuration();
        $config->setLogger($this->getMockLogger());
        $config->parse($masterConfig);
        $result = $config->getConfiguration();

        $this->assertArrayNotHasKey('import', $result);

        foreach ($rules['keys'] as $key => $expectedValue)
        {
            $this->assertEquals($expectedValue, $result[$key]);
        }
    }

    public function testConfigurationImportDirectoryFails()
    {
        $masterConfig = $this->writeTempFile('_config.yml', "import:\n  - hello/");
        $this->writeTempFile('hello/world.yml', 'dummy file');

        $config = new Configuration();
        $config->setLogger($this->getReadableLogger());
        $config->parse($masterConfig);

        $this->assertContains("can't import a directory", StreamInterceptor::$output);
    }

    public function testConfigurationImportSelfFails()
    {
        $masterConfig = $this->writeTempFile('_config.yml', "import:\n  - _config.yml");

        $config = new Configuration();
        $config->setLogger($this->getReadableLogger());
        $config->parse($masterConfig);

        $this->assertContains("can't import yourself", StreamInterceptor::$output);
    }

    public function testConfigurationImportNonYamlFails()
    {
        $masterConfig = $this->writeTempFile('_config.yml', "import:\n  - my_xml.xml");
        $this->writeTempFile('my_xml.xml', '<root></root>');

        $config = new Configuration();
        $config->setLogger($this->getReadableLogger());
        $config->parse($masterConfig);

        $this->assertContains('a non-YAML configuration', StreamInterceptor::$output);
    }

    public function testConfigurationImportSymlinkFails()
    {
        $masterConfig = $this->writeTempFile('_config.yml', "import:\n  - my_sym.yml");
        $this->writeTempFile('original_file.yml', 'value: one');
        $this->fs->symlink(
            $this->fs->appendPath($this->assetFolder, 'original_file.yml'),
            $this->fs->appendPath($this->assetFolder, 'my_sym.yml')
        );

        $config = new Configuration();
        $config->setLogger($this->getReadableLogger());
        $config->parse($masterConfig);

        $this->assertContains('a symbolically linked file', StreamInterceptor::$output);
    }

    public function testConfigurationImportFileNotFound()
    {
        $masterConfig = $this->writeTempFile('_config.yml', "import:\n  - fake_file.yml");

        $config = new Configuration();
        $config->setLogger($this->getReadableLogger());
        $config->parse($masterConfig);

        $this->assertContains('could not find file to import', StreamInterceptor::$output);
    }

    public function testConfigurationImportGrandchild()
    {
        $masterConfig = $this->writeTempFile('grandparent.yml', "import:\n  - parent.yml\ngrandparent_value: 1");
        $this->writeTempFile('parent.yml', "import:\n  - child.yml\nparent_value: 2");
        $this->writeTempFile('child.yml', 'child_value: 3');

        $config = new Configuration();
        $config->setLogger($this->getMockLogger());
        $config->parse($masterConfig);
        $result = $config->getConfiguration();

        $this->assertArrayHasKey('grandparent_value', $result);
        $this->assertArrayHasKey('parent_value', $result);
        $this->assertArrayHasKey('child_value', $result);
    }

    public function testConfigurationImportRecursivelyFails()
    {
        $masterConfig = $this->writeTempFile('_config.yml', "import:\n  - _second.yml");
        $this->writeTempFile('_second.yml', "import:\n  - _config.yml");

        $config = new Configuration();
        $config->setLogger($this->getReadableLogger());
        $config->parse($masterConfig);

        $this->assertContains("can't recursively import a file", StreamInterceptor::$output);
    }

    public static function dataProviderInvalidImportArrays()
    {
        return array(
            array("import:\n- 1"),
            array("import:\n- true"),
            array("import:\n- 2017-01-01"),
        );
    }

    /**
     * @dataProvider dataProviderInvalidImportArrays
     *
     * @param string $invalidImportArray
     */
    public function testConfigurationInvalidImportArrayFails($invalidImportArray)
    {
        $configPath = $this->writeTempFile('config.yml', $invalidImportArray);

        $config = new Configuration();
        $config->setLogger($this->getReadableLogger());
        $config->parse($configPath);

        $this->assertContains('invalid import:', StreamInterceptor::$output);
    }

    public static function dataProviderInvalidImports()
    {
        return array(
            array('import: true'),
            array('import: ~'),
            array('import: 1234'),
            array('import: butter & toast'),
        );
    }

    /**
     * @dataProvider dataProviderInvalidImports
     *
     * @param string $invalidImport
     */
    public function testConfigurationImportNotAnArray($invalidImport)
    {
        $configPath = $this->writeTempFile('config.yml', $invalidImport);

        $config = new Configuration();
        $config->setLogger($this->getReadableLogger());
        $config->parse($configPath);

        $this->assertContains('the reserved "import" keyword can only be an array', StreamInterceptor::$output);
    }

    public function testDeprecatedBase()
    {
        $configPath = $this->writeTempFile('_config.yml', 'base: /my-deprecated');

        $config = new Configuration();
        $config->setLogger($this->getMockLogger());
        $config->parse($configPath);

        $this->assertEquals('/my-deprecated', $config->getBaseUrl());
    }

    public function testDeprecatedBasePriority()
    {
        $configPath = $this->writeTempFile('_config.yml', "base: /my-deprecated\nbaseurl: /my-new");

        $config = new Configuration();
        $config->setLogger($this->getMockLogger());
        $config->parse($configPath);

        $this->assertEquals('/my-new', $config->getBaseUrl());
    }
}
