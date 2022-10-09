<?php declare(strict_types=1);

/**
 * @copyright 2018 Vladimir Jimenez
 * @license   https://github.com/stakx-io/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Test\FrontMatter;

use allejo\stakx\Templating\Twig\TwigStakxBridge;
use allejo\stakx\Test\StakxTestCase;
use Twig\Environment;

/**
 * @internal
 *
 * @coversNothing
 */
class FrontMatterDocumentTest extends StakxTestCase
{
    public static function provideHasTwigDependencyRegexCases(): iterable
    {
        return [
            [
                "{% for d in data['name'] %}",
                'data', 'name', true,
            ],
            [
                "{%- for d in data['name'] -%}",
                'data', 'name', true,
            ],
            [
                "{% for d in data['name'] -%}",
                'data', 'name', true,
            ],
            [
                "{%- for d in data['name'] %}",
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
     * @dataProvider provideHasTwigDependencyRegexCases
     */
    public function testHasTwigDependencyRegex(mixed $twig, mixed $namespace, mixed $needle, mixed $match): void
    {
        $deps = $this->getTwigBridge()->getAssortmentDependencies($namespace, $twig);

        $this->assertEquals($match, in_array($needle, $deps) || (is_null($needle) && !empty($deps)));
    }

    public static function provideHasImportDependencyRegexCases(): iterable
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
     * @dataProvider provideHasImportDependencyRegexCases
     */
    public function testHasImportDependencyRegex(mixed $twig, mixed $needle): void
    {
        $deps = $this->getTwigBridge()->getTemplateImportDependencies($twig);

        $this->assertContains($needle, $deps);
    }

    private function getTwigBridge(): TwigStakxBridge
    {
        $environment = $this->getMockBuilder(Environment::class)
            ->disableOriginalConstructor()
            ->getMock()
        ;

        return new TwigStakxBridge($environment);
    }
}
