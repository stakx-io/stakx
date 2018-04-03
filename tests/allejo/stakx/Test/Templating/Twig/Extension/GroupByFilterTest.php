<?php

/**
 * @copyright 2017 Vladimir Jimenez
 * @license   https://github.com/allejo/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Test\Templating\Twig\Extension;

use allejo\stakx\Test\PHPUnit_Stakx_TestCase;
use allejo\stakx\Templating\Twig\Extension\GroupByFilter;

class GroupByFilterTests extends PHPUnit_Stakx_TestCase
{
    public function testGroupByFilter()
    {
        $original = array(
            'a' => array('id' => 20, 'name' => 'chimpanzee'),
            'b' => array('id' => 40, 'name' => 'meeting'),
            'c' => array('id' => 20, 'name' => 'dynasty'),
            'd' => array('id' => 50, 'name' => 'chocolate'),
            'e' => array('id' => 10, 'name' => 'bananas'),
            'f' => array('id' => 50, 'name' => 'fantasy'),
            'g' => array('id' => 50, 'name' => 'football'),
        );

        $expected = array(
            '10' => array(
                'e' => array('id' => 10, 'name' => 'bananas'),
            ),
            '20' => array(
                'a' => array('id' => 20, 'name' => 'chimpanzee'),
                'c' => array('id' => 20, 'name' => 'dynasty'),
            ),
            '40' => array(
                'b' => array('id' => 40, 'name' => 'meeting'),
            ),
            '50' => array(
                'd' => array('id' => 50, 'name' => 'chocolate'),
                'f' => array('id' => 50, 'name' => 'fantasy'),
                'g' => array('id' => 50, 'name' => 'football'),
            ),
        );

        $gbFilter = new GroupByFilter();
        $grouped = $gbFilter($original, 'id');

        $this->assertEquals($expected, $grouped);
    }

    public function testGroupByFilterContentItems()
    {
        $books = $this->bookCollectionProvider()['books'];
        $filter = new GroupByFilter();
        $grouped = $filter($books, 'publisher');

        $this->assertArrayHasKey('Candlewick', $grouped);
        $this->assertArrayHasKey('Random House Books for Young Readers', $grouped);

        foreach ($grouped as $publisher => $books)
        {
            foreach ($books as $book)
            {
                $this->assertEquals($publisher, $book['publisher']);
            }
        }
    }

    public function testGroupByFilterBooleanFrontMatterKey()
    {
        $books = $this->bookCollectionProvider()['books'];
        $filter = new GroupByFilter();
        $grouped = $filter($books, 'completed');

        $this->assertArrayHasKey('true', $grouped);
        $this->assertArrayHasKey('false', $grouped);

        foreach ($grouped['true'] as $item)
        {
            $this->assertTrue($item['completed']);
        }

        foreach ($grouped['false'] as $item)
        {
            $this->assertFalse($item['completed']);
        }
    }

    public function testGroupByFilterNullFrontMatterKey()
    {
        $books = $this->bookCollectionProvider()['books'];
        $filter = new GroupByFilter();
        $grouped = $filter($books, 'non-existent-key');

        $this->assertCount(0, $grouped);
    }

    public function testGroupByFilterArrayThrowsWarning()
    {
        $this->setExpectedException(\PHPUnit_Framework_Error_Warning::class);

        $books = $this->bookCollectionProvider()['books'];
        $filter = new GroupByFilter();
        $filter($books, 'animals');
    }
}
