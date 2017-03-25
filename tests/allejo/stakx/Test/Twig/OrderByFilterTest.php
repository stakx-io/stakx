<?php

/**
 * @copyright 2017 Vladimir Jimenez
 * @license   https://github.com/allejo/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Test\Twig;

use allejo\stakx\Test\PHPUnit_Stakx_TestCase;
use allejo\stakx\Twig\OrderFilter;

class OrderByFilterTest extends PHPUnit_Stakx_TestCase
{
    public static function dataProvider()
    {
        return array(
            array(
                array(
                    array(
                        'name' => 'Order of Bacon',
                        'sort' => 30,
                    ),
                    array(
                        'name' => 'Whee',
                        'sort' => 0,
                    ),
                    array(
                        'name' => 'Side order of fries',
                        'sort' => 3,
                    ),
                ),
            ),
        );
    }

    /**
     * @dataProvider dataProvider
     *
     * @param array $dataset
     */
    public function testOrderFilterAscOnArray($dataset)
    {
        $orderFilter = new OrderFilter();
        $result = $orderFilter($dataset, 'sort');
        $expected = array(
            array(
                'name' => 'Whee',
                'sort' => 0,
            ),
            array(
                'name' => 'Side order of fries',
                'sort' => 3,
            ),
            array(
                'name' => 'Order of Bacon',
                'sort' => 30,
            ),
        );

        $this->assertEquals($expected, $result);
    }

    /**
     * @dataProvider dataProvider
     *
     * @param array $dataset
     */
    public function testOrderFilterDescOnArray($dataset)
    {
        $orderFilter = new OrderFilter();
        $result = $orderFilter($dataset, 'sort', 'DESC');
        $expected = array(
            array(
                'name' => 'Order of Bacon',
                'sort' => 30,
            ),
            array(
                'name' => 'Side order of fries',
                'sort' => 3,
            ),
            array(
                'name' => 'Whee',
                'sort' => 0,
            ),
        );

        $this->assertEquals($expected, $result);
    }

    public static function keyProvider()
    {
        return array(
            array('page_count'),
            array('publisher'),
        );
    }

    /**
     * @dataProvider keyProvider
     *
     * @param $sortKey
     */
    public function testOrderFilterOnFrontMatterNumericAsc($sortKey)
    {
        $books = $this->bookCollectionProvider()['books'];
        $orderFilter = new OrderFilter();
        $results = $orderFilter($books, $sortKey);
        $lastCount = -1;

        foreach ($results as $result) {
            $this->assertGreaterThanOrEqual($lastCount, $result[$sortKey]);
            $lastCount = $result[$sortKey];
        }
    }

    /**
     * @dataProvider keyProvider
     *
     * @param $sortKey
     */
    public function testOrderFilterOnFrontMatterNumericDesc($sortKey)
    {
        $books = $this->bookCollectionProvider()['books'];
        $orderFilter = new OrderFilter();
        $results = $orderFilter($books, $sortKey, 'DESC');
        $lastCount = 999999;

        foreach ($results as $result) {
            $this->assertLessThanOrEqual($lastCount, $result[$sortKey]);
            $lastCount = $result[$sortKey];
        }
    }

    public function testOrderFilterNonArray()
    {
        $myString = 'Hello World';
        $orderFilter = new OrderFilter();
        $results = $orderFilter($myString, 'some-key');

        $this->assertEquals($myString, $results);
    }
}
