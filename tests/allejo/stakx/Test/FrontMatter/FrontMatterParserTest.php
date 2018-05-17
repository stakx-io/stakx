<?php

/**
 * @copyright 2018 Vladimir Jimenez
 * @license   https://github.com/stakx-io/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Test\FrontMatter;

use allejo\stakx\FrontMatter\Exception\YamlUnsupportedVariableException;
use allejo\stakx\FrontMatter\Exception\YamlVariableUndefinedException;
use allejo\stakx\FrontMatter\ExpandedValue;
use allejo\stakx\FrontMatter\FrontMatterParser;
use allejo\stakx\Test\PHPUnit_Stakx_TestCase;
use Symfony\Component\Yaml\Yaml;

class FrontMatterParserTest extends PHPUnit_Stakx_TestCase
{
    public function testPrimitiveVariableUndefinedThrowsException()
    {
        $this->setExpectedException(YamlVariableUndefinedException::class);

        $frontMatter = [
            'var' => '%undefinedVar',
        ];

        (new FrontMatterParser($frontMatter))->parse();
    }

    public function testComplexVariableUndefinedThrowsException()
    {
        $this->setExpectedException(YamlVariableUndefinedException::class);

        $frontMatter = [
            'var' => '%{site.undefinedVar}',
        ];

        (new FrontMatterParser($frontMatter))->parse();
    }

    public function testVariableStringReplacement()
    {
        $eval = 'variable value';
        $frontMatter = [
            'myVar' => $eval,
            'evaluated' => '%myVar butter',
        ];

        $fmp = new FrontMatterParser($frontMatter);
        $fmp->parse();

        $this->assertEquals($eval . ' butter', $frontMatter['evaluated']);
        $this->assertFalse($fmp->hasExpansion());
    }

    public function testExternalVariableStringReplacement()
    {
        $eval = 'My Super Site';
        $frontMatter = [
            'evaluated' => '%{site.title}',
        ];

        $fmp = new FrontMatterParser($frontMatter);
        $fmp->addComplexVariables([
            'site' => [
                'title' => $eval,
            ],
        ]);
        $fmp->parse();

        $this->assertEquals($eval, $frontMatter['evaluated']);
    }

    public function testVariableIntReplacement()
    {
        $eval = 5154;
        $frontMatter = [
            'myVar' => $eval,
            'evaluated' => '%myVar1200',
        ];

        (new FrontMatterParser($frontMatter))->parse();

        $this->assertEquals(51541200, $frontMatter['evaluated']);
    }

    public function testVariableBoolReplacementThrowsException()
    {
        $this->setExpectedException(YamlUnsupportedVariableException::class);

        $eval = true;
        $frontMatter = [
            'myVar' => $eval,
            'evaluated' => '%myVar',
        ];

        (new FrontMatterParser($frontMatter))->parse();
    }

    public function testVariableArrayReplacementThrowsException()
    {
        $this->setExpectedException(YamlUnsupportedVariableException::class);

        $frontMatter = [
            'myVar' => [
                'one',
                'two',
                'three',
            ],
            'evaluated' => '%myVar',
        ];

        (new FrontMatterParser($frontMatter))->parse();
    }

    public function testMultidimensionalArrayValueExpansionThrowsException()
    {
        $this->setExpectedException(YamlUnsupportedVariableException::class);

        $frontMatter = [
            'languages' => [
                'first' => [
                    'en',
                    'fr',
                ],
            ],
            'permalink' => '/blog/%languages/',
        ];

        (new FrontMatterParser($frontMatter))->parse();
    }

    public function testExpandedValueAsString()
    {
        $val = '/blog/en/';
        $eval = new ExpandedValue($val);
        $this->assertEquals($val, (string)$eval);
    }

    public function testVariableStringValueExpansion()
    {
        $frontMatter = [
            'languages' => [
                'en',
                'fr',
            ],
            'permalink' => '/blog/%languages/',
        ];

        $fmp = new FrontMatterParser($frontMatter);
        $fmp->parse();

        $firstEval = new ExpandedValue('/blog/en/');
        $firstEval->setIterator('languages', 'en');

        $secondEval = new ExpandedValue('/blog/fr/');
        $secondEval->setIterator('languages', 'fr');

        $expected = [
            'languages' => $frontMatter['languages'],
            'permalink' => [
                [
                    $firstEval,
                    $secondEval,
                ],
            ],
        ];

        $this->assertEquals($expected, $frontMatter);
        $this->assertTrue($fmp->hasExpansion());
    }

    public function testVariableStringValueExpansionFromExternalVariables()
    {
        $frontMatter = [
            'permalink' => '/categories/%{site.categories}/',
        ];

        $fmp = new FrontMatterParser($frontMatter);
        $fmp->addComplexVariables([
            'site' => [
                'categories' => [
                    'misc',
                    'updates',
                ],
            ],
        ]);
        $fmp->parse();

        $firstEval = new ExpandedValue('/categories/misc/');
        $firstEval->setIterator('site.categories', 'misc');

        $secondEval = new ExpandedValue('/categories/updates/');
        $secondEval->setIterator('site.categories', 'updates');

        $expected = [
            'permalink' => [
                [
                    $firstEval,
                    $secondEval,
                ],
            ],
        ];

        $this->assertEquals($expected, $frontMatter);
        $this->assertTrue($fmp->hasExpansion());
    }

    public function testVariableMultipleStringValueExpansion()
    {
        $frontMatter = [
            'status' => [
                'final',
                'drafts',
            ],
            'languages' => [
                'en',
                'fr',
            ],
            'permalink' => '/blog/%languages/%status/',
        ];
        $fmp = new FrontMatterParser($frontMatter);
        $fmp->parse();

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

        $expected = [
            'status' => $frontMatter['status'],
            'languages' => $frontMatter['languages'],
            'permalink' => [
                [
                    $firstEval,
                    $secondEval,
                    $thirdEval,
                    $fourthEval,
                ],
            ],
        ];

        $this->assertEquals($expected, $frontMatter);
        $this->assertTrue($fmp->hasExpansion());
    }

    public function testVariableArrayValueExpansion()
    {
        $frontMatter = [
            'languages' => [
                'en',
                'fr',
            ],
            'permalink' => [
                '/blog/%languages/',
                '/el-blog/%languages/',
            ],
        ];

        $fmp = new FrontMatterParser($frontMatter);
        $fmp->parse();

        $firstEval = new ExpandedValue('/blog/en/');
        $firstEval->setIterator('languages', 'en');

        $secondEval = new ExpandedValue('/blog/fr/');
        $secondEval->setIterator('languages', 'fr');

        $thirdEval = new ExpandedValue('/el-blog/en/');
        $thirdEval->setIterator('languages', 'en');

        $fourthEval = new ExpandedValue('/el-blog/fr/');
        $fourthEval->setIterator('languages', 'fr');

        $expected = [
            'languages' => $frontMatter['languages'],
            'permalink' => [
                [
                    $firstEval,
                    $secondEval,
                ],
                [
                    $thirdEval,
                    $fourthEval,
                ],
            ],
        ];

        $this->assertEquals($expected, $frontMatter);
        $this->assertTrue($fmp->hasExpansion());
    }

    public function testSpecialFieldsDataAsDateTime()
    {
        $frontMatter = [
            'date' => new \DateTime('2016-05-31'),
        ];

        (new FrontMatterParser($frontMatter))->parse();

        $this->assertEquals(2016, $frontMatter['year']);
        $this->assertEquals(5, $frontMatter['month']);
        $this->assertEquals(31, $frontMatter['day']);
    }

    public function testSpecialFieldsDateAsString()
    {
        $frontMatter = [
            'date' => '2016-01-26',
        ];

        (new FrontMatterParser($frontMatter))->parse();

        $this->assertEquals(2016, $frontMatter['year']);
        $this->assertEquals(1, $frontMatter['month']);
        $this->assertEquals(26, $frontMatter['day']);
    }

    public function testSpecialFieldsDateAsEpochLosAngeles()
    {
        date_default_timezone_set('America/Los_Angeles');

        // In the LA timezone, this Epoch should be the 7th of March
        $frontMatter = [
            'date' => 1457395200,
        ];

        (new FrontMatterParser($frontMatter))->parse();

        $this->assertEquals(2016, $frontMatter['year']);
        $this->assertEquals(3, $frontMatter['month']);
        $this->assertEquals(7, $frontMatter['day']);
    }

    public function testSpecialFieldsDateAsEpochUTC()
    {
        date_default_timezone_set('UTC');

        // In UTC, this Epoch should be the 8th of March
        $frontMatter = [
            'date' => 1457395200,
        ];

        (new FrontMatterParser($frontMatter))->parse();

        $this->assertEquals(2016, $frontMatter['year']);
        $this->assertEquals(3, $frontMatter['month']);
        $this->assertEquals(8, $frontMatter['day']);
    }

    public function testSpecialFieldsDateFromYaml()
    {
        $frontMatter = Yaml::parse("date: '2016-02-28'");

        (new FrontMatterParser($frontMatter))->parse();

        $this->assertEquals(2016, $frontMatter['year']);
        $this->assertEquals(2, $frontMatter['month']);
        $this->assertEquals(28, $frontMatter['day']);
    }

    public function testSpecialKeyBaseNameDefinedByParser()
    {
        $frontMatter = [
            'myVar' => '/blog/%basename/',
        ];

        (new FrontMatterParser($frontMatter, [
            'basename' => 'hello-world',
        ]))->parse();

        $this->assertEquals('/blog/hello-world/', $frontMatter['myVar']);
    }

    public function testSpecialKeyFileNameDefinedByParser()
    {
        $frontMatter = [
            'myVar' => '/blog/%filename/',
        ];

        (new FrontMatterParser($frontMatter, [
            'filename' => 'hello-world.md',
        ]))->parse();

        $this->assertEquals('/blog/hello-world.md/', $frontMatter['myVar']);
    }

    public function testSpecialKeyTakesPrecedenceOverFrontMatter()
    {
        $frontMatter = [
            'value' => 'foo',
        ];

        (new FrontMatterParser($frontMatter, [
            'value' => 'bar',
        ]))->parse();

        $this->assertEquals('bar', $frontMatter['value']);
    }
}
