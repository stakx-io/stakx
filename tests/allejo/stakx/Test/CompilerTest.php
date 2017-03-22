<?php

/**
 * @copyright 2017 Vladimir Jimenez
 * @license   https://github.com/allejo/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Test;

use allejo\stakx\Compiler;
use allejo\stakx\Manager\TwigManager;
use allejo\stakx\Object\Configuration;
use allejo\stakx\Object\PageView;
use allejo\stakx\System\Folder;
use org\bovigo\vfs\vfsStream;

class CompilerTest extends PHPUnit_Stakx_TestCase
{
    /** @var Folder */
    private $folder;

    public function setUp()
    {
        parent::setUp();

        vfsStream::create(array(
            '_site' => array(),
        ));

        $config = new Configuration();
        $config->parseConfiguration();

        $twigEnv = new TwigManager();
        $twigEnv->configureTwig($config, array(
            'safe' => false,
            'globals' => array(),
        ));

        $this->folder = new Folder($this->rootDir->getChild('_site')->url());
    }

    public static function dataProviderStaticPageViews()
    {
        return array(
            array('/toast.html', 'toast.html'),
            array('/hello-world/', 'hello-world/index.html'),
            array('/foo/bar/data.json', 'foo/bar/data.json'),
            array('/static-page', 'static-page'),
            array('/release-0.1.0-name/', 'release-0.1.0-name/index.html'),
        );
    }

    /**
     * @dataProvider dataProviderStaticPageViews
     *
     * @param string $permalink
     * @param string $targetPath
     */
    public function testStaticPageViewWrite($permalink, $targetPath)
    {
        $pageView = $this->createVirtualFile(PageView::class, array('permalink' => $permalink));

        $compiler = new Compiler();
        $compiler->setLogger($this->getMockLogger());
        $compiler->setTargetFolder($this->folder);
        $compiler->setPageViews(array($pageView));
        $compiler->compileAll();

        $this->assertTrue($this->fs->exists(vfsStream::url('root/_site/' . $targetPath)));
    }
}
