<?php
/**
 * @copyright 2017 Vladimir Jimenez
 * @license   https://github.com/allejo/stakx/blob/master/LICENSE.md MIT
 */

use allejo\stakx\Manager\PageManager;
use allejo\stakx\Object\Configuration;
use allejo\stakx\System\Filesystem;
use allejo\stakx\System\Folder;
use Psr\Log\LoggerInterface;

class PageManagerTest extends PHPUnit_Stakx_TestCase
{
    private $pageManager;

    public function setUp ()
    {
        parent::setUp();

        mkdir(__DIR__ . '/output');

        $outputDir = new Folder(__DIR__ . '/output');
        $config    = new Configuration();
        $config->parseConfiguration($this->fs->appendPath(__DIR__, 'assets', 'ConfigurationFiles', 'simple.yml'));

        $this->pageManager = new PageManager();
        $this->pageManager->setLogger($this->loggerMock());
        $this->pageManager->setTargetFolder($outputDir);
        $this->pageManager->parsePageViews(array(
            $this->fs->appendPath(__DIR__, 'assets', 'PageViews')
        ));
        $this->pageManager->configureTwig($config, array(
            'safe' => false,
            'globals' => array()
        ));
        $this->pageManager->compileAll();
    }

    public function tearDown()
    {
        $this->fs->remove(__DIR__ . '/output');
    }

    public function testRepeaterTemplateCreation ()
    {
        $breakfast = __DIR__ . '/output/menu/breakfast/index.html';
        $this->assertFileExistsAndContains($breakfast, 'meals:breakfast');
        $this->assertFileExistsAndContains($breakfast, '/menu/breakfast/');

        $lunch = __DIR__ . '/output/menu/lunch/index.html';
        $this->assertFileExistsAndContains($lunch, 'meals:lunch');
        $this->assertFileExistsAndContains($lunch, '/menu/lunch/');

        $dinner = __DIR__ . '/output/menu/dinner/index.html';
        $this->assertFileExistsAndContains($dinner, 'meals:dinner');
        $this->assertFileExistsAndContains($dinner, '/menu/dinner/');
    }
}
