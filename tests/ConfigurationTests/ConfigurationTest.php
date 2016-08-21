<?php

namespace allejo\stakx\tests;

use allejo\stakx\Object\Configuration;
use org\bovigo\vfs\vfsStream;
use PHPUnit_Framework_TestCase;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Exception\ParseException;

class ConfigurationTests extends PHPUnit_Framework_TestCase
{
    /**
     * @var Configuration
     */
    private $sampleConfig;

    /**
     * @var Configuration
     */
    private $defaultConfig;

    public function setup ()
    {
        $output = $this->getMock(OutputInterface::class);
        $this->sampleConfig = new Configuration(__DIR__ . '/assets/sample.yml', $output);
        $this->defaultConfig = new Configuration(null, $output);
    }

    public function testSampleConfigIsDebug ()
    {
        $this->assertTrue($this->sampleConfig->isDebug());
    }

    public function testSampleConfigGetBaseUrl ()
    {
        $this->assertEquals('super-toast', $this->sampleConfig->getBaseUrl());
    }

    public function testSampleConfigGetDataFolders ()
    {
        $this->assertCount(1, $this->sampleConfig->getDataFolders());
        $this->assertEquals(array('_data'), $this->sampleConfig->getDataFolders());
    }

    public function testSampleConfigGetDataSets ()
    {
        $this->assertCount(1, $this->sampleConfig->getDataSets());
        $this->assertEquals(array(
            array(
                'name' => 'months',
                'folder' => '_datasets/months'
            )
        ), $this->sampleConfig->getDataSets());
    }

    public function testSampleConfigCombinedInclude ()
    {
        $this->assertCount(2, $this->sampleConfig->getIncludes());
        $this->assertContains('.htaccess', $this->sampleConfig->getIncludes());
        $this->assertContains('include.html', $this->sampleConfig->getIncludes());
    }

    public function testSampleConfigCombinedExclude ()
    {
        $this->assertCount(3, $this->sampleConfig->getExcludes());
        $this->assertContains('node_modules', $this->sampleConfig->getExcludes());
        $this->assertContains('exclude.html', $this->sampleConfig->getExcludes());
    }

    public function testSampleConfigGetTheme ()
    {
        $this->assertEquals('bootstrap', $this->sampleConfig->getTheme());
    }

    public function testSampleConfigGetCustomOptions ()
    {
        $options = $this->sampleConfig->getConfiguration();

        $this->assertEquals('Sample Title', $options['title']);
        $this->assertEquals('http://facebook.com', $options['social']['facebook']);
        $this->assertEquals('http://twitter.com',  $options['social']['twitter']);
    }

    public function testSampleConfigGetPageViews()
    {
        $this->assertCount(3, $this->sampleConfig->getPageViewFolders());
        $this->assertContains('_pages', $this->sampleConfig->getPageViewFolders());
        $this->assertContains('_foo', $this->sampleConfig->getPageViewFolders());
    }

    public function testSampleConfigGetTargetFolder ()
    {
        $this->assertEquals('_bacon', $this->sampleConfig->getTargetFolder());
    }

    public function testSampleConfigGetCollectionsFolders ()
    {
        $collections = $this->sampleConfig->getCollectionsFolders();

        $this->assertCount(2, $collections);
        $this->assertContains(array(
            'name' => 'posts',
            'folder' => '_posts'
        ), $collections);
    }

    public function testDefaultConfigTwigEscape ()
    {
        $this->assertFalse($this->defaultConfig->getTwigAutoescape());
    }

    public function testDefaultConfigBaseUrl ()
    {
        $this->assertEmpty($this->defaultConfig->getBaseUrl());
    }

    public function testDefaultConfigTargetUrl ()
    {
        $this->assertEquals('_site', $this->defaultConfig->getTargetFolder());
    }

    public function testInvalidConfigFile ()
    {
        $output = $this->getMock(OutputInterface::class);

        $file = vfsStream::newFile('_config.yml');
        $root = vfsStream::setup();

        $file->setContent('invalid yaml')
             ->at($root);

        $config = new Configuration($file->url(), $output);

        // This is part of the default configuration, so we should expect it here
        $this->assertEquals('_site', $config->getTargetFolder());
    }
}