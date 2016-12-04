<?php

use allejo\stakx\Engines\FrontMatterParser;
use allejo\stakx\Exception\YamlUnsupportedVariableException;
use allejo\stakx\Exception\YamlVariableUndefinedException;

class FrontMatterParserTest extends PHPUnit_Framework_TestCase
{
    public function testVariableUndefinedThrowsException ()
    {
        $this->setExpectedException(YamlVariableUndefinedException::class);

        $frontMatter = array(
            'var' => '%undefinedVar'
        );

        new FrontMatterParser($frontMatter);
    }

    public function testVariableStringReplacement ()
    {
        $eval = 'variable value';
        $frontMatter = array(
            'myVar' => $eval,
            'evaluated' => '%myVar butter'
        );

        $fmp = new FrontMatterParser($frontMatter);

        $this->assertEquals($eval . ' butter', $frontMatter['evaluated']);
        $this->assertFalse($fmp->hasExpansion());
    }

    public function testVariableIntReplacement ()
    {
        $eval = 5154;
        $frontMatter = array(
            'myVar' => $eval,
            'evaluated' => '%myVar1200'
        );

        new FrontMatterParser($frontMatter);

        $this->assertEquals(51541200, $frontMatter['evaluated']);
    }

    public function testVariableBoolReplacementThrowsException ()
    {
        $this->setExpectedException(YamlUnsupportedVariableException::class);

        $eval = true;
        $frontMatter = array(
            'myVar' => $eval,
            'evaluated' => '%myVar'
        );

        new FrontMatterParser($frontMatter);
    }

    public function testVariableArrayReplacementThrowsException ()
    {
        $this->setExpectedException(YamlUnsupportedVariableException::class);

        $frontMatter = array(
            'myVar' => array(
                'one',
                'two',
                'three'
            ),
            'evaluated' => '%myVar'
        );

        new FrontMatterParser($frontMatter);
    }

    public function testMultidimensionalArrayValueExpansionThrowsException ()
    {
        $this->setExpectedException(YamlUnsupportedVariableException::class);

        $frontMatter = array(
            'languages' => array(
                'first' => array(
                    'en',
                    'fr'
                )
            ),
            'permalink' => '/blog/%languages/'
        );

        new FrontMatterParser($frontMatter);
    }

    public function testVariableStringValueExpansion ()
    {
        $frontMatter = array(
            'languages' => array(
                'en',
                'fr'
            ),
            'permalink' => '/blog/%languages/'
        );

        $fmp = new FrontMatterParser($frontMatter);

        $expected = array(
            'languages' => $frontMatter['languages'],
            'permalink' => array(
                array(
                    array(
                        'evaluated' => '/blog/en/',
                        'iterator'  => array(
                            'languages' => 'en'
                        )
                    ),
                    array(
                        'evaluated' => '/blog/fr/',
                        'iterator'  => array(
                            'languages' => 'fr'
                        )
                    )
                )
            )
        );

        $this->assertEquals($expected, $frontMatter);
        $this->assertTrue($fmp->hasExpansion());
    }

    public function testVariableArrayValueExpansion ()
    {
        $frontMatter = array(
            'languages' => array(
                'en',
                'fr'
            ),
            'permalink' => array(
                '/blog/%languages/',
                '/el-blog/%languages/'
            )
        );

        $fmp = new FrontMatterParser($frontMatter);

        $expected = array(
            'languages' => $frontMatter['languages'],
            'permalink' => array(
                array(
                    array(
                        'evaluated' => '/blog/en/',
                        'iterator'  => array(
                            'languages' => 'en'
                        )
                    ),
                    array(
                        'evaluated' => '/blog/fr/',
                        'iterator'  => array(
                            'languages' => 'fr'
                        )
                    )
                ),
                array(
                    array(
                        'evaluated' => '/el-blog/en/',
                        'iterator'  => array(
                            'languages' => 'en'
                        )
                    ),
                    array(
                        'evaluated' => '/el-blog/fr/',
                        'iterator'  => array(
                            'languages' => 'fr'
                        )
                    )
                )
            )
        );

        $this->assertEquals($expected, $frontMatter);
        $this->assertTrue($fmp->hasExpansion());
    }
}