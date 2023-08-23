<?php

/**
 * @copyright 2023 Vladimir Jimenez
 * @license   https://github.com/stakx-io/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Test\Templating\Twig\Extension;

use allejo\stakx\MarkupEngine\MarkupEngineManager;
use allejo\stakx\Templating\Twig\Extension\AFunction;
use allejo\stakx\Templating\Twig\TwigExtension;
use allejo\stakx\Test\PHPUnit_Stakx_TestCase;
use PHPUnit_Framework_MockObject_MockObject as MockObject;

class AFunctionTest extends PHPUnit_Stakx_TestCase
{
    /** @var \Twig_Environment */
    private $twig_env;

    public function setUp()
    {
        parent::setUp();

        /** @var MockObject|MarkupEngineManager $markupEngine */
        $markupEngine = $this->getMock(MarkupEngineManager::class);

        $extension = new TwigExtension($markupEngine);
        $extension->addFilters([new AFunction()]);

        $loader = new \Twig_Loader_Filesystem();
        $this->twig_env = new \Twig_Environment($loader);
        $this->twig_env->addExtension($extension);
    }

    public static function dataProvider()
    {

        return [
            [
                '<a href="/">home</a>', '/', 'home', [], []
            ],
            [
                '<a href="/" class="link dark">classy home</a>', '/', 'classy home', ['class' => 'link dark'], []
            ],
            [
                '<a href="https://example.com">external</a>', 'https://example.com', 'external', [], []
            ],
            [
                '<a href="https://example.com" rel="nofollow" target="_blank">external with config</a>', 'https://example.com', 'external with config', [], [
                    'externalrel' => 'nofollow',
                    'externaltarget' => '_blank'
                ]
            ],
            [
                '<a href="https://example.com" target="_blank">external with config and override</a>', 'https://example.com', 'external with config and override', ['rel' => ''], [
                    'externalrel' => 'nofollow',
                    'externaltarget' => '_blank'
                ]
            ],
            [
                '<a href="https://internalhost.example.com">internal</a>', 'https://internalhost.example.com', 'internal', [], [
                    'internalhosts' => ['internalhost.example.com'],
                    'externalrel' => 'nofollow',
                    'externaltarget' => '_blank'
                ]
            ],
        ];
    }

    /**
     * @dataProvider dataProvider
     *
     * @param $expected
     * @param $href
     * @param $text
     * @param $attributes
     * @param $linksConfig
     */
    public function testAFunction($expected, $href, $text, $attributes, $linksConfig)
    {
        $this->twig_env->addGlobal('site', [
            'links' => $linksConfig,
        ]);

        $filter = new AFunction();
        $html = $filter($this->twig_env, $href, $text, $attributes);

        $this->assertEquals($expected, $html);
    }
}
