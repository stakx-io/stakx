<?php

/**
 * @copyright 2017 Vladimir Jimenez
 * @license   https://github.com/allejo/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Test\FrontMatter;

use allejo\stakx\FrontMatter\FrontMatterDocument;
use allejo\stakx\Test\PHPUnit_Stakx_TestCase;
use \PHPUnit_Framework_MockObject_MockObject as MockObject;

class FrontMatterDocumentTest extends PHPUnit_Stakx_TestCase
{
    public static function dataProviderTwigDependencyTests()
    {
        return [
            [
                "{% for d in data['name'] %}",
                'data', 'name', true
            ],
            [
                "{% for d in collections.name %}",
                'collections', 'name', true
            ],
            [
                "{% for d in collections.name | order('date', 'DESC') %}",
                'collections', 'name', true
            ],
            [
                '{% for d in collections["underscore_name"] %}',
                'collections', 'underscore_name', true
            ],
            [
                "{% for d in collections.underscore_name | order('date', 'DESC') %}",
                'collections', 'underscore_name', true
            ],
            [
                "{% for d in collections['underscore_name'] | order('date', 'DESC') %}",
                'collections', 'underscore_name', true
            ],
            [
                "{% for d in collections.name|order('date', 'DESC') %}",
                'collections', 'name', true
            ],
            [
                "{% for d in collections.name[1] %}",
                'collections', 'name', true
            ],
            [
                "{% for d in collections.h3ll0 %}",
                'collections', 'h3ll0', true
            ],
            [
                "{% for d in data.toc.title %}",
                'data', 'toc', true
            ],
            [
                "{% for d in data %}",
                'data', null, true
            ],
            [
                "{%for d in data%}",
                'data', null, true
            ],
            [
                "{% if collections %}",
                'collections', null, true
            ],
            [
                "{% set foo = collections %}",
                'collections', null, true
            ],
            [
                "{{ data }}",
                'data', null, true
            ],
            [
                "{{ data.name }}",
                'data', 'name', true
            ],
            [
                "{{ data['name'] }}",
                'data', 'name', true
            ],
            [
                "{{ data_world }}",
                'data', null, false
            ],
            [
                "{% set foo = bar %}",
                'data', null, false
            ],
            [
                "{% set data = for %}",
                'data', null, false
            ],
            [
                "{% set rar = data[her] %}",
                'data', null, true
            ],
        ];
    }

    /**
     * @dataProvider dataProviderTwigDependencyTests
     *
     * @param $twig
     * @param $namespace
     * @param $needle
     */
    public function testHasTwigDependencyRegex($twig, $namespace, $needle, $match)
    {
        $file = $this->createStub($this->createVirtualFilePath(array(), $twig));
        $this->assertEquals($match, $file->hasTwigDependency($namespace, $needle));
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
                '_includes/header.html.twig'
            ],
            [
                '{% include "_includes/header.html.twig" %}',
                '_includes/header.html.twig'
            ],
            [
                '{% include "_includes/footer.html.twig" with { footer: [] } %}',
                '_includes/footer.html.twig'
            ],
        ];
    }

    /**
     * @dataProvider dataProviderTwigImportTests
     */
    public function testHasImportDependencyRegex($twig, $needle)
    {
        $file = $this->createStub($this->setAndCreateVirtualFrontMatterFileObject([], $twig));
        $this->assertTrue($file->hasImportDependency($needle));
    }

    /**
     * @param $filePath
     *
     * @return FrontMatterDocument|MockObject
     */
    private function createStub($filePath)
    {
        return $this
            ->getMockBuilder(FrontMatterDocument::class)
            ->setConstructorArgs(array($filePath))
            ->getMockForAbstractClass()
        ;
    }
}
