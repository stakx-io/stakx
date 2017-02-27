<?php

use allejo\stakx\FrontMatter\ExpandedValue;
use allejo\stakx\FrontMatter\FrontMatterParser;
use allejo\stakx\FrontMatter\YamlUnsupportedVariableException;
use allejo\stakx\FrontMatter\YamlVariableUndefinedException;
use Symfony\Component\Yaml\Yaml;

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

        $firstEval = new ExpandedValue('/blog/en/');
        $firstEval->setIterator('languages', 'en');

        $secondEval = new ExpandedValue('/blog/fr/');
        $secondEval->setIterator('languages', 'fr');

        $expected = array(
            'languages' => $frontMatter['languages'],
            'permalink' => array(
                array(
                    $firstEval,
                    $secondEval
                )
            )
        );

        $this->assertEquals($expected, $frontMatter);
        $this->assertTrue($fmp->hasExpansion());
    }

    public function testVariableMultipleStringValueExpansion ()
    {
        $frontMatter = array(
            'status' => array(
                'final',
                'drafts'
            ),
            'languages' => array(
                'en',
                'fr'
            ),
            'permalink' => '/blog/%languages/%status/'
        );
        $fmp = new FrontMatterParser($frontMatter);

        $firstEval = new ExpandedValue('/blog/en/final/');
        $firstEval->setIterator('languages', 'en');
        $firstEval->setIterator('status', 'final');

        $secondEval = new ExpandedValue('/blog/en/drafts/');
        $secondEval->setIterator('languages', 'en');
        $secondEval->setIterator('status', 'drafts');

        $thirdEval = new ExpandedValue('/blog/fr/final/');
        $thirdEval->setIterator('languages', 'fr');
        $thirdEval->setIterator('status', 'final');

        $fourthEval = new ExpandedValue('/blog/fr/drafts/');
        $fourthEval->setIterator('languages', 'fr');
        $fourthEval->setIterator('status', 'drafts');

        $expected = array(
            'status' => $frontMatter['status'],
            'languages' => $frontMatter['languages'],
            'permalink' => array(
                array(
                    $firstEval,
                    $secondEval,
                    $thirdEval,
                    $fourthEval
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

        $firstEval = new ExpandedValue('/blog/en/');
        $firstEval->setIterator('languages', 'en');

        $secondEval = new ExpandedValue('/blog/fr/');
        $secondEval->setIterator('languages', 'fr');

        $thirdEval = new ExpandedValue('/el-blog/en/');
        $thirdEval->setIterator('languages', 'en');

        $fourthEval = new ExpandedValue('/el-blog/fr/');
        $fourthEval->setIterator('languages', 'fr');

        $expected = array(
            'languages' => $frontMatter['languages'],
            'permalink' => array(
                array(
                    $firstEval,
                    $secondEval
                ),
                array(
                    $thirdEval,
                    $fourthEval
                )
            )
        );

        $this->assertEquals($expected, $frontMatter);
        $this->assertTrue($fmp->hasExpansion());
    }

    public function testSepcialFieldsDataAsDateTime ()
    {
        $frontMatter = array(
            'date' => new DateTime('2016-05-31')
        );

        new FrontMatterParser($frontMatter);

        $this->assertEquals(2016, $frontMatter['year']);
        $this->assertEquals(5, $frontMatter['month']);
        $this->assertEquals(31, $frontMatter['day']);
    }

    public function testSpecialFieldsDateAsString ()
    {
        $frontMatter = array(
            'date' => '2016-01-26'
        );

        new FrontMatterParser($frontMatter);

        $this->assertEquals(2016, $frontMatter['year']);
        $this->assertEquals(1, $frontMatter['month']);
        $this->assertEquals(26, $frontMatter['day']);
    }

    public function testSpecialFieldsDateAsEpochLosAngeles ()
    {
        date_default_timezone_set('America/Los_Angeles');

        // In the LA timezone, this Epoch should be the 7th of March
        $frontMatter = array(
            'date' => 1457395200
        );

        new FrontMatterParser($frontMatter);

        $this->assertEquals(2016, $frontMatter['year']);
        $this->assertEquals(3, $frontMatter['month']);
        $this->assertEquals(7, $frontMatter['day']);
    }

    public function testSpecialFieldsDateAsEpochUTC ()
    {
        date_default_timezone_set('UTC');

        // In UTC, this Epoch should be the 8th of March
        $frontMatter = array(
            'date' => 1457395200
        );

        new FrontMatterParser($frontMatter);

        $this->assertEquals(2016, $frontMatter['year']);
        $this->assertEquals(3, $frontMatter['month']);
        $this->assertEquals(8, $frontMatter['day']);
    }

    public function testSpecialFieldsDateFromYaml ()
    {
        $frontMatter = Yaml::parse("date: '2016-02-28'");

        new FrontMatterParser($frontMatter);

        $this->assertEquals(2016, $frontMatter['year']);
        $this->assertEquals(2, $frontMatter['month']);
        $this->assertEquals(28, $frontMatter['day']);
    }
}