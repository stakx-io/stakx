<?php

namespace allejo\stakx\tests;

use allejo\stakx\Twig\WhereFilter;
use PHPUnit_Framework_TestCase;

class WhereFilterTests extends PHPUnit_Framework_TestCase
{
    private $dataset;

    public function setUp ()
    {
        $this->dataset = array(
            array(
                'name' => 'One Five',
                'slug' => 'chimpanzee',
                'cost' => 20,
                'tags' => array('fun', 'monkey', 'banana')
            ),
            array(
                'name' => 'Two One',
                'slug' => 'meeting',
                'cost' => 40,
                'tags' => array('fun', 'purple', 'red')
            ),
            array(
                'name' => 'Three Two',
                'slug' => 'dynasty',
                'cost' => 20,
                'tags' => array('monkey', 'animal', 'zoo')
            ),
            array(
                'name' => 'Four One',
                'slug' => 'chocolate',
                'cost' => 50,
                'tags' => array('fruit', 'port', 'computer')
            ),
            array(
                'name' => 'Five Five',
                'slug' => 'bananas',
                'cost' => 10,
                'tags' => array('vegetable', 'purple', 'red')
            ),
            array(
                'name' => 'Six Three',
                'slug' => 'fantasy',
                'cost' => 50,
                'tags' => array('fruit', 'orange', 'purple')
            )
        );
    }

    public function dataProvider ()
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
            array('assertContains', 'name', '~=', 'One')
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
    public function testWhereFilter ($fxn, $key, $comparison, $value)
    {
        $whereFilter = new WhereFilter();
        $filtered = $whereFilter($this->dataset, $key, $comparison, $value);

        foreach ($filtered as $item)
        {
            $this->$fxn($value, $item[$key]);
        }
    }

    public function testInvalidFilterEmptyResult ()
    {
        $this->setExpectedException(\Twig_Error_Syntax::class);

        $whereFilter = new WhereFilter();
        $whereFilter($this->dataset, 'name', 'non-existent-comparison', 'the_map');
    }

    public function testInvalidKeyEmptyResult ()
    {
        $whereFilter = new WhereFilter();
        $filtered = $whereFilter($this->dataset, 'non-existent-key', '==', 'the_map');

        $this->assertEmpty($filtered);
    }

    public function testGetTwigSimpleFilter ()
    {
        $twigSimpleFilter = WhereFilter::get();

        $this->assertInstanceOf(\Twig_SimpleFilter::class, $twigSimpleFilter);
    }
}