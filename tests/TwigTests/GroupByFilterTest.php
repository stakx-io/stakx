<?php

namespace allejo\stakx\tests;

use allejo\stakx\Twig\GroupByFilter;
use PHPUnit_Framework_TestCase;

class GroupByFilterTests extends PHPUnit_Framework_TestCase
{
    public function testGroupByFilter ()
    {
        $original = array(
            'a' => array('id' => 20, 'name' => 'chimpanzee'),
            'b' => array('id' => 40, 'name' => 'meeting'),
            'c' => array('id' => 20, 'name' => 'dynasty'),
            'd' => array('id' => 50, 'name' => 'chocolate'),
            'e' => array('id' => 10, 'name' => 'bananas'),
            'f' => array('id' => 50, 'name' => 'fantasy'),
            'g' => array('id' => 50, 'name' => 'football')
        );

        $expected = array(
            '10' => array(
                'e' => array('id' => 10, 'name' => 'bananas')
            ),
            '20' => array(
                'a' => array('id' => 20, 'name' => 'chimpanzee'),
                'c' => array('id' => 20, 'name' => 'dynasty')
            ),
            '40' => array(
                'b' => array('id' => 40, 'name' => 'meeting'),
            ),
            '50' => array(
                'd' => array('id' => 50, 'name' => 'chocolate'),
                'f' => array('id' => 50, 'name' => 'fantasy'),
                'g' => array('id' => 50, 'name' => 'football')
            )
        );

        $gbFilter = new GroupByFilter();
        $grouped = $gbFilter($original, 'id');

        $this->assertEquals($expected, $grouped);
    }
}