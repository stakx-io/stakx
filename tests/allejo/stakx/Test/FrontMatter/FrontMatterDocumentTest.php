<?php

/**
 * @copyright 2017 Vladimir Jimenez
 * @license   https://github.com/allejo/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Test\FrontMatter;

use allejo\stakx\FrontMatter\FrontMatterDocument;
use allejo\stakx\Test\PHPUnit_Stakx_TestCase;

class FrontMatterDocumentTest extends PHPUnit_Stakx_TestCase
{
    public static function dataProviderTwigDependencyTests()
    {
        return array(
            array(
                "{% for d in data['name'] %}",
                'data', 'name', true
            ),
            array(
                "{% for d in collections.name %}",
                'collections', 'name', true
            ),
            array(
                "{% for d in collections.name | order('date', 'DESC') %}",
                'collections', 'name', true
            ),
            array(
                '{% for d in collections["underscore_name"] %}',
                'collections', 'underscore_name', true
            ),
            array(
                "{% for d in collections.underscore_name | order('date', 'DESC') %}",
                'collections', 'underscore_name', true
            ),
            array(
                "{% for d in collections['underscore_name'] | order('date', 'DESC') %}",
                'collections', 'underscore_name', true
            ),
            array(
                "{% for d in collections.name|order('date', 'DESC') %}",
                'collections', 'name', true
            ),
            array(
                "{% for d in collections.name[1] %}",
                'collections', 'name', true
            ),
            array(
                "{% for d in collections.h3ll0 %}",
                'collections', 'h3ll0', true
            ),
            array(
                "{% for d in data.toc.title %}",
                'data', 'toc', true
            ),
            array(
                "{% for d in data %}",
                'data', null, true
            ),
            array(
                "{%for d in data%}",
                'data', null, true
            ),
            array(
                "{% if collections %}",
                'collections', null, true
            ),
            array(
                "{% set foo = collections %}",
                'collections', null, true
            ),
            array(
                "{{ data }}",
                'data', null, true
            ),
            array(
                "{{ data.name }}",
                'data', 'name', true
            ),
            array(
                "{{ data['name'] }}",
                'data', 'name', true
            ),
            array(
                "{{ data_world }}",
                'data', null, false
            ),
            array(
                "{% set foo = bar %}",
                'data', null, false
            ),
            array(
                "{% set data = for %}",
                'data', null, false
            ),
            array(
                "{% set rar = data[her] %}",
                'data', null, true
            ),
        );
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

    /**
     * @param $filePath
     *
     * @return FrontMatterDocument
     */
    private function createStub($filePath)
    {
        return $this->getMockBuilder(FrontMatterDocument::class)
            ->setConstructorArgs(array($filePath))
            ->getMockForAbstractClass();
    }
}
