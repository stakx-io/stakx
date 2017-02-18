<?php

namespace allejo\stakx\tests;

use allejo\stakx\Twig\GroupByFilter;

class GroupByFilterTests extends \PHPUnit_Stakx_TestCase
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

    public function testGroupByFilterContentItems ()
    {
        $books = $this->bookCollectionProvider()['books'];
        $filter = new GroupByFilter();
        $grouped = $filter($books, 'publisher');

        $this->assertCount(2, $grouped);
        $this->assertArrayHasKey('Candlewick', $grouped);
        $this->assertArrayHasKey('Random House Books for Young Readers', $grouped);

        $this->assertCount(3, $grouped['Candlewick']);
        $this->assertCount(2, $grouped['Random House Books for Young Readers']);
    }

    public function testGroupByFilterBooleanFrontMatterKey ()
    {
        $books = $this->bookCollectionProvider()['books'];
        $filter = new GroupByFilter();
        $grouped = $filter($books, 'completed');

        $this->assertCount(2, $grouped);
        $this->assertArrayHasKey('true', $grouped);
        $this->assertArrayHasKey('false', $grouped);
        $this->assertCount(3, $grouped['true']);
        $this->assertCount(1, $grouped['false']);
    }

    public function testGroupByFilterNullFrontMatterKey ()
    {
        $books = $this->bookCollectionProvider()['books'];
        $filter = new GroupByFilter();
        $grouped = $filter($books, 'non-existent-key');

        $this->assertCount(0, $grouped);
    }
}