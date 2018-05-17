<?php

/**
 * @copyright 2018 Vladimir Jimenez
 * @license   https://github.com/stakx-io/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Test\Templating\Twig\Extension;

use allejo\stakx\Templating\Twig\Extension\OrderFilter;
use allejo\stakx\Test\PHPUnit_Stakx_TestCase;

class OrderByFilterTest extends PHPUnit_Stakx_TestCase
{
    public static function dataProvider()
    {
        return [
            [
                [
                    [
                        'name' => 'Order of Bacon',
                        'sort' => 30,
                    ],
                    [
                        'name' => 'Whee',
                        'sort' => 0,
                    ],
                    [
                        'name' => 'Side order of fries',
                        'sort' => 3,
                    ],
                ],
            ],
        ];
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
        $expected = [
            [
                'name' => 'Whee',
                'sort' => 0,
            ],
            [
                'name' => 'Side order of fries',
                'sort' => 3,
            ],
            [
                'name' => 'Order of Bacon',
                'sort' => 30,
            ],
        ];

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
        $expected = [
            [
                'name' => 'Order of Bacon',
                'sort' => 30,
            ],
            [
                'name' => 'Side order of fries',
                'sort' => 3,
            ],
            [
                'name' => 'Whee',
                'sort' => 0,
            ],
        ];

        $this->assertEquals($expected, $result);
    }

    public static function keyProvider()
    {
        return [
            ['page_count'],
            ['publisher'],
        ];
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

        foreach ($results as $result)
        {
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

        foreach ($results as $result)
        {
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
