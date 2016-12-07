<?php

use allejo\stakx\Manager\PageManager;
use allejo\stakx\Object\Configuration;
use allejo\stakx\System\Filesystem;
use allejo\stakx\System\Folder;
use Psr\Log\LoggerInterface;

class PageManagerTest extends PHPUnit_Framework_TestCase
{
    private $pageManager;

    public function setUp ()
    {
        mkdir(__DIR__ . '/output');

        $outputDir = new Folder(__DIR__ . '/output');
        $config    = new Configuration();
        $config->parseConfiguration(__DIR__ . '/_config.yml');

        $this->pageManager = new PageManager();
        $this->pageManager->setLogger($this->loggerMock());
        $this->pageManager->setTargetFolder($outputDir);
        $this->pageManager->parsePageViews(array(
            __DIR__ . '/PageViews/'
        ));
        $this->pageManager->configureTwig($config, array(
            'safe' => false,
            'globals' => array()
        ));
        $this->pageManager->compileAll();
    }

    public function tearDown()
    {
        $fs = new Filesystem();
        $fs->remove(__DIR__ . '/output');
    }

    public function testRepeaterTemplateCreation ()
    {
        $this->assertFileExists(__DIR__ . '/output/menu/breakfast/index.html');
        $this->assertFileExists(__DIR__ . '/output/menu/lunch/index.html');
        $this->assertFileExists(__DIR__ . '/output/menu/dinner/index.html');
    }

    /**
     * @return LoggerInterface
     */
    private function loggerMock ()
    {
        return $this->getMock(LoggerInterface::class);
    }
}
