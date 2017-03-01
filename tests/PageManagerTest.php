<?php
/**
 * @copyright 2017 Vladimir Jimenez
 * @license   https://github.com/allejo/stakx/blob/master/LICENSE.md MIT
 */

use allejo\stakx\Manager\CollectionManager;
use allejo\stakx\Manager\PageManager;
use allejo\stakx\Object\Configuration;
use allejo\stakx\Object\JailObject;
use allejo\stakx\System\Folder;

class PageManagerTest extends PHPUnit_Stakx_TestCase
{
    /** @var PageManager */
    private $pageManager;

    public function setUp ()
    {
        parent::setUp();

        $this->fs->remove(__DIR__ . '/output');
        mkdir(__DIR__ . '/output');

        $outputDir = new Folder(__DIR__ . '/output');
        $config    = new Configuration();
        $config->parseConfiguration($this->fs->appendPath(__DIR__, 'assets', 'ConfigurationFiles', 'simple.yml'));

        $collectionManager = new CollectionManager();
        $collectionManager->setLogger($this->loggerMock());
        $collectionManager->parseCollections(array(
            array(
                'name' => 'books',
                'folder' => $this->fs->appendPath(__DIR__, 'assets', 'MyBookCollection')
            )
        ));

        $this->pageManager = new PageManager();
        $this->pageManager->setLogger($this->loggerMock());
        $this->pageManager->setTargetFolder($outputDir);
        $this->pageManager->setCollections($collectionManager->getCollections());
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

    public function testSiteMenu ()
    {
        $menu = $this->pageManager->getSiteMenu();

        $this->assertArrayHasKey('static', $menu);
        $this->assertArrayHasKey('child-1', $menu['static']->getChildren());
        $this->assertInstanceOf(JailObject::class, $menu['static']);

        $this->assertArrayNotHasKey('authors', $menu);
        $this->assertArrayNotHasKey('child-2', $menu['static']->getChildren());
    }

    public function testSitePagesList ()
    {
        $pages = $this->pageManager->getFlatPages();

        $this->assertArrayHasKey('Static Page', $pages);
        $this->assertArrayHasKey('Static Child 1', $pages);
        $this->assertArrayNotHasKey('Repeater Page', $pages);

        $this->assertInstanceOf(JailObject::class, $pages['Static Page']);
    }
}
