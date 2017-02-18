<?php

namespace allejo\stakx\tests;

use allejo\stakx\Object\PageView;
use allejo\stakx\System\Filesystem;
use allejo\stakx\Twig\BaseUrlFunction;
use allejo\stakx\Twig\TwigExtension;

class BaseUrlFilterTest extends \PHPUnit_Stakx_TestCase
{
    /** @var \Twig_Environment */
    private $twig_env;

    public function setUp ()
    {
        parent::setUp();

        $loader = new \Twig_Loader_Filesystem();
        $this->twig_env = new \Twig_Environment($loader);
        $this->twig_env->addExtension(new TwigExtension());
    }

    public static function dataProvider ()
    {
        $fs = new Filesystem();

        return array(
            array('/toast/link.html', '/toast/', 'link.html'),
            array('/toast/link.html', '/toast', '/link.html'),
            array('/toast/link.html', '/toast/', '//link.html'),
            array('/toast/link.html', '/toast//', '/link.html'),
            array('/toast/link.html', 'toast', '/link.html'),
            array('/toast/link.html', 'toast/', 'link.html'),
            array('/toast/butter/', 'toast/', 'butter/'),
            array('/toast/butter/', '//toast/', '//butter///'),
            array('/toast/', 'toast', null),
            array('/toast/bacon/', 'toast', array(
                'permalink' => 'bacon/'
            )),
            array('/toast/bacon', 'toast', array(
                'permalink' => 'bacon'
            )),
            array('/toast/', 'toast', array(
                'some-key' => 'bacon/'
            )),
            array('/toast/static/', 'toast', (
                new PageView($fs->appendPath(__DIR__, '..', 'assets', 'PageViews', 'static.html.twig'))
            ))
        );
    }

    /**
     * @dataProvider dataProvider
     *
     * @param $expected
     * @param $base
     * @param $assetPath
     */
    public function testBaseUrlFilter ($expected, $base, $assetPath)
    {
        $this->twig_env->addGlobal('site', array(
            'baseurl' => $base
        ));

        $filter = new BaseUrlFunction();
        $url = $filter($this->twig_env, $assetPath);

        $this->assertEquals($expected, $url);
    }
}