<?php

/**
 * @copyright 2017 Vladimir Jimenez
 * @license   https://github.com/allejo/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Test\Twig;

use allejo\stakx\Command\BuildableCommand;
use allejo\stakx\Document\ContentItem;
use allejo\stakx\Service;
use allejo\stakx\Test\PHPUnit_Stakx_TestCase;
use allejo\stakx\Twig\WhereFilter;

class WhereFilterTests extends PHPUnit_Stakx_TestCase
{
    private $dataset;

    public function setUp()
    {
        parent::setUp();

        Service::setParameter(BuildableCommand::USE_DRAFTS, true);

        $this->dataset = array(
            array(
                'name' => 'One Five',
                'slug' => 'chimpanzee',
                'cost' => 20,
                'tags' => array('fun', 'monkey', 'banana'),
            ),
            array(
                'name' => 'Two One',
                'slug' => 'meeting',
                'cost' => 40,
                'tags' => array('fun', 'purple', 'red', 'Bacon'),
            ),
            array(
                'name' => 'Three Two',
                'slug' => 'dynasty',
                'cost' => 20,
                'tags' => array('monkey', 'animal', 'zoo'),
            ),
            array(
                'name' => 'Four One',
                'slug' => 'chocolate',
                'cost' => 50,
                'tags' => array('fruit', 'port', 'computer'),
            ),
            array(
                'name' => 'Five Five',
                'slug' => 'bananas',
                'cost' => 10,
                'tags' => array('vegetable', 'purple', 'red', 'Bacon'),
            ),
            array(
                'name' => 'Six Three',
                'slug' => 'fantasy',
                'cost' => 50,
                'tags' => array('fruit', 'orange', 'purple'),
            ),
        );
    }

    public static function dataProvider()
    {
        return array(
            array('assertEquals', 'cost', '==', 50),
            array('assertEquals', 'cost', '==', 20),
            array('assertEquals', 'slug', '==', 'meeting'),

            // Weakly typed comparisons should fail
            array('assertNotEquals', 'cost', '==', '50'),
            array('assertNotEquals', 'cost', '==', '20'),
            array('assertNotEquals', 'cost', '!=', 10),
            array('assertNotEquals', 'cost', '!=', 20),
            array('assertNotEquals', 'slug', '!=', 'meeting'),
            array('assertGreaterThan', 'cost', '>', 20),
            array('assertGreaterThan', 'cost', '>', 50),
            array('assertGreaterThanOrEqual', 'cost', '>=', 40),
            array('assertGreaterThanOrEqual', 'cost', '>=', 50),
            array('assertLessThan', 'cost', '<', 40),
            array('assertLessThan', 'cost', '<', 10),
            array('assertLessThanOrEqual', 'cost', '<=', 50),
            array('assertLessThanOrEqual', 'cost', '<=', 30),
            array('assertContains', 'tags', '~=', 'purple'),
            array('assertContains', 'name', '~=', 'One'),
        );
    }

    /**
     * @dataProvider dataProvider
     *
     * @param string $fxn        The assertion function to test
     * @param string $key        The array key we'll be checking
     * @param string $comparison The comparison we'll be using
     * @param mixed  $value      The value we are looking for
     */
    public function testWhereFilter($fxn, $key, $comparison, $value)
    {
        $whereFilter = new WhereFilter();
        $filtered = $whereFilter($this->dataset, $key, $comparison, $value);

        foreach ($filtered as $item) {
            $this->$fxn($value, $item[$key]);
        }
    }

    public function testInvalidFilterEmptyResult()
    {
        $this->setExpectedException(\Twig_Error_Syntax::class);

        $whereFilter = new WhereFilter();
        $whereFilter($this->dataset, 'name', 'non-existent-comparison', 'the_map');
    }

    public function testInvalidKeyEmptyResult()
    {
        $whereFilter = new WhereFilter();
        $filtered = $whereFilter($this->dataset, 'non-existent-key', '==', 'the_map');

        $this->assertEmpty($filtered);
    }

    public function testGetTwigSimpleFilter()
    {
        $twigSimpleFilter = WhereFilter::get();

        $this->assertInstanceOf(\Twig_SimpleFilter::class, $twigSimpleFilter);
    }

    public function testWhereFilterAgainstContentItem()
    {
        $elements = [
            $this->createFrontMatterDocumentOfType(ContentItem::class, null, [
                'aggregate' => 'toast',
                'category' => 'warm',
            ]),
            $this->createFrontMatterDocumentOfType(ContentItem::class, null, [
                'aggregate' => 'bacon',
                'category' => 'warm',
            ]),
            $this->createFrontMatterDocumentOfType(ContentItem::class, null, [
                'aggregate' => 'pancake',
                'category' => 'cold',
            ]),
        ];

        foreach ($elements as $element)
        {
            $element->evaluateFrontMatter();
        }

        $whereFilter = new WhereFilter();
        $filteredAggregate = $whereFilter($elements, 'aggregate', '==', 'toast');
        $filteredCategory = $whereFilter($elements, 'category', '==', 'warm');

        $this->assertCount(1, $filteredAggregate);
        $this->assertCount(2, $filteredCategory);
    }

    public static function fmDataProvider()
    {
        return [
            ['completed', '==', true, 3],
            ['completed', '==', false, 2],
            ['completed', '==', null, 1],
            ['completed', '!=', false, 4],
            ['completed', '~=', false, 0],
            ['page_count', '>=', 200, 2],
            ['page_count', '~=', 200, 0],
            ['shipping_weight', '>', 6, 3],
            ['shipping_weight', '>=', 12.6, 2],
            ['shipping_weight', '<', 12.6, 3],
            ['shipping_weight', '<=', 12.6, 5],
            ['publisher', '~=', 'Candle', 3],
            ['publisher', '~=', 'candle', 0],
            ['publisher', '~=', 'R', 2],
            ['publisher', '_=', 'candle', 3],
            ['publisher', '_=', 'r', 2],
            ['animals', '_=', 'Dog', 2],
            ['publisher', '/=', '/.wick.?/', 3],
        ];
    }

    /**
     * @dataProvider fmDataProvider
     *
     * @param string $fm         The front matter key we'll be checking
     * @param string $comparison The comparison we'll be using
     * @param mixed  $value      The value we're looking for the front matter to match
     * @param int    $count      The amount of entries we're expecting to match this rule
     */
    public function testWhereFilterContentItemAssertCount($fm, $comparison, $value, $count)
    {
        $collections = $this->bookCollectionProvider(true);
        $books = $collections['books'];
        $filter = new WhereFilter();

        $trueResults = $filter($books, $fm, $comparison, $value);
        $this->assertCount($count, $trueResults);
    }
}
