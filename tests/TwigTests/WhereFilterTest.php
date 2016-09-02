<?php

namespace allejo\stakx\tests;

use allejo\stakx\Twig\WhereFilter;
use PHPUnit_Framework_TestCase;

class WhereFilterTests extends PHPUnit_Framework_TestCase
{
    private $dataset;

    public function setUp()
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
            array('assertLessThanOrEqual', 'cost', '<=', 30)
        );
    }

    /**
     * @dataProvider dataProvider
     *
     * @param $fxn
     * @param $key
     * @param $comparison
     * @param $value
     */
    public function testWhereFilter($fxn, $key, $comparison, $value)
    {
        $whereFilter = new WhereFilter();
        $filtered = $whereFilter($this->dataset, $key, $comparison, $value);

        foreach ($filtered as $item)
        {
            $this->$fxn($value, $item[$key]);
        }
    }

    public function testWhereContainsFilterArray()
    {
        $tag = 'purple';
        $whereFilter = new WhereFilter();
        $filtered = $whereFilter($this->dataset, 'tags', '~=', $tag);

        foreach ($filtered as $item)
        {
            $this->assertContains($tag, $item['tags']);
        }
    }

    public function testWhereContainsFilterString()
    {
        $tag = 'One';
        $whereFilter = new WhereFilter();
        $filtered = $whereFilter($this->dataset, 'name', '~=', $tag);

        foreach ($filtered as $item)
        {
            $this->assertContains($tag, $item['name']);
        }
    }
}