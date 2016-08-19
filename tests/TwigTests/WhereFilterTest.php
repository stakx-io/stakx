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
            array('cost' => 20, 'name' => 'chimpanzee'),
            array('cost' => 40, 'name' => 'meeting'),
            array('cost' => 20, 'name' => 'dynasty'),
            array('cost' => 50, 'name' => 'chocolate'),
            array('cost' => 10, 'name' => 'bananas'),
            array('cost' => 50, 'name' => 'fantasy'),
            array('cost' => 50, 'name' => 'football')
        );
    }

    public function dataProvider ()
    {
        return array(
            array('assertEquals', 'cost', '==', 50),
            array('assertEquals', 'cost', '==', 20),
            array('assertEquals', 'name', '==', 'meeting'),

            // Weakly typed comparisons should fail
            array('assertNotEquals', 'cost', '==', '50'),
            array('assertNotEquals', 'cost', '==', '20'),
            array('assertNotEquals', 'cost', '!=', 10),
            array('assertNotEquals', 'cost', '!=', 20),
            array('assertNotEquals', 'name', '!=', 'meeting'),
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
}