<?php

/**
 * @copyright 2018 Vladimir Jimenez
 * @license   https://github.com/stakx-io/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Test\FrontMatter;

use allejo\stakx\Templating\Twig\TwigStakxBridge;
use allejo\stakx\Test\PHPUnit_Stakx_TestCase;

class FrontMatterDocumentTest extends PHPUnit_Stakx_TestCase
{
    public static function dataProviderTwigDependencyTests()
    {
        return [
            [
                "{% for d in data['name'] %}",
                'data', 'name', true,
            ],
            [
                '{% for d in collections.name %}',
                'collections', 'name', true,
            ],
            [
                "{% for d in collections.name | order('date', 'DESC') %}",
                'collections', 'name', true,
            ],
            [
                '{% for d in collections["underscore_name"] %}',
                'collections', 'underscore_name', true,
            ],
            [
                "{% for d in collections.underscore_name | order('date', 'DESC') %}",
                'collections', 'underscore_name', true,
            ],
            [
                "{% for d in collections['underscore_name'] | order('date', 'DESC') %}",
                'collections', 'underscore_name', true,
            ],
            [
                "{% for d in collections.name|order('date', 'DESC') %}",
                'collections', 'name', true,
            ],
            [
                '{% for d in collections.name[1] %}',
                'collections', 'name', true,
            ],
            [
                '{% for d in collections.h3ll0 %}',
                'collections', 'h3ll0', true,
            ],
            [
                '{% for d in data.toc.title %}',
                'data', 'toc', true,
            ],
            [
                '{% for d in data %}',
                'data', null, true,
            ],
            [
                '{%for d in data%}',
                'data', null, true,
            ],
            [
                '{% if collections %}',
                'collections', null, true,
            ],
            [
                '{% set foo = collections %}',
                'collections', null, true,
            ],
            [
                '{{ data }}',
                'data', null, true,
            ],
            [
                '{{ data.name }}',
                'data', 'name', true,
            ],
            [
                "{{ data['name'] }}",
                'data', 'name', true,
            ],
            [
                '{{ data_world }}',
                'data', null, false,
            ],
            [
                '{% set foo = bar %}',
                'data', null, false,
            ],
            [
                '{% set data = for %}',
                'data', null, false,
            ],
            [
                '{% set rar = data[her] %}',
                'data', null, true,
            ],
        ];
    }

    /**
     * @dataProvider dataProviderTwigDependencyTests
     *
     * @param $twig
     * @param $namespace
     * @param $needle
     * @param mixed $match
     */
    public function testHasTwigDependencyRegex($twig, $namespace, $needle, $match)
    {
        $bridge = new TwigStakxBridge($this->getMock(\Twig_Environment::class));
        $deps = $bridge->getAssortmentDependencies($namespace, $twig);

        $this->assertEquals($match, (in_array($needle, $deps) || (is_null($needle) && !empty($deps))));
    }

    public static function dataProviderTwigImportTests()
    {
        return [
            [
                "{% import 'forms.html' as forms %}",
                'forms.html',
            ],
            [
                '{% import "forms.html" as forms %}',
                'forms.html',
            ],
            [
                "{% from 'forms.html' import input as input_field, textarea %}",
                'forms.html',
            ],
            [
                '{% from "forms.html" import input as input_field, textarea %}',
                'forms.html',
            ],
            [
                "{% include '_includes/header.html.twig' %}",
                '_includes/header.html.twig',
            ],
            [
                '{% include "_includes/header.html.twig" %}',
                '_includes/header.html.twig',
            ],
            [
                '{% include "_includes/footer.html.twig" with { footer: [] } %}',
                '_includes/footer.html.twig',
            ],
        ];
    }

    /**
     * @dataProvider dataProviderTwigImportTests
     *
     * @param mixed $twig
     * @param mixed $needle
     */
    public function testHasImportDependencyRegex($twig, $needle)
    {
        $bridge = new TwigStakxBridge($this->getMock(\Twig_Environment::class));
        $deps = $bridge->getTemplateImportDependencies($twig);

        $this->assertContains($needle, $deps);
    }
}
