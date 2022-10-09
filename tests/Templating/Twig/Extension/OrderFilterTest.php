<?php declare(strict_types=1);

/**
 * @copyright 2018 Vladimir Jimenez
 * @license   https://github.com/stakx-io/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Test\Templating\Twig\Extension;

use allejo\stakx\Templating\Twig\Extension\OrderFilter;
use allejo\stakx\Test\StakxTestCase;

/**
 * @internal
 *
 * @coversNothing
 */
class OrderFilterTest extends StakxTestCase
{
    public static function dataProvider_singleLevelArray(): iterable
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
                    [
                        'name' => 'super size me',
                        'sort' => 10,
                    ],
                ],
            ],
        ];
    }

    /**
     * @dataProvider dataProvider_singleLevelArray
     *
     * @param array $dataset
     */
    public function testOrderFilterAscOnArray($dataset): void
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
                'name' => 'super size me',
                'sort' => 10,
            ],
            [
                'name' => 'Order of Bacon',
                'sort' => 30,
            ],
        ];

        $this->assertEquals($expected, $result);
    }

    /**
     * @dataProvider dataProvider_singleLevelArray
     *
     * @param array $dataset
     */
    public function testOrderFilterDescOnArray($dataset): void
    {
        $orderFilter = new OrderFilter();
        $result = $orderFilter($dataset, 'sort', 'DESC');
        $expected = [
            [
                'name' => 'Order of Bacon',
                'sort' => 30,
            ],
            [
                'name' => 'super size me',
                'sort' => 10,
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

    /**
     * @dataProvider dataProvider_singleLevelArray
     *
     * @param array $dataset
     */
    public function testOrderFilterAscOnArrayCaseSensitive($dataset): void
    {
        $orderFilter = new OrderFilter();
        $result = $orderFilter($dataset, 'name', 'ASC');
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
            [
                'name' => 'super size me',
                'sort' => 10,
            ],
        ];

        $this->assertEquals($expected, $result);
    }

    /**
     * @dataProvider dataProvider_singleLevelArray
     *
     * @param array $dataset
     */
    public function testOrderFilterDescOnArrayCaseSensitive($dataset): void
    {
        $orderFilter = new OrderFilter();
        $result = $orderFilter($dataset, 'name', 'DESC');
        $expected = [
            [
                'name' => 'super size me',
                'sort' => 10,
            ],
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
     * @dataProvider dataProvider_singleLevelArray
     *
     * @param array $dataset
     */
    public function testOrderFilterAscOnArrayCaseInsensitive($dataset): void
    {
        $orderFilter = new OrderFilter();
        $result = $orderFilter($dataset, 'name', 'ASC', true);
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
                'name' => 'super size me',
                'sort' => 10,
            ],
            [
                'name' => 'Whee',
                'sort' => 0,
            ],
        ];

        $this->assertEquals($expected, $result);
    }

    /**
     * @dataProvider dataProvider_singleLevelArray
     *
     * @param array $dataset
     */
    public function testOrderFilterDescOnArrayCaseInsensitive($dataset): void
    {
        $orderFilter = new OrderFilter();
        $result = $orderFilter($dataset, 'name', 'DESC', true);
        $expected = [
            [
                'name' => 'Whee',
                'sort' => 0,
            ],
            [
                'name' => 'super size me',
                'sort' => 10,
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

    public static function dataProvider_multiLevelArray(): iterable
    {
        return [
            [
                [
                    [
                        'name' => 'Order of Bacon',
                        'metadata' => [
                            'sort' => 30,
                        ],
                    ],
                    [
                        'name' => 'Whee',
                        'metadata' => [
                            'sort' => 0,
                        ],
                    ],
                    [
                        'name' => 'Side order of fries',
                        'metadata' => [
                            'sort' => 3,
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * @dataProvider dataProvider_multiLevelArray
     *
     * @param array $dataset
     */
    public function testOrderFilterAscNestedArray($dataset): void
    {
        $orderFilter = new OrderFilter();
        $result = $orderFilter($dataset, 'metadata.sort');
        $expected = [
            [
                'name' => 'Whee',
                'metadata' => [
                    'sort' => 0,
                ],
            ],
            [
                'name' => 'Side order of fries',
                'metadata' => [
                    'sort' => 3,
                ],
            ],
            [
                'name' => 'Order of Bacon',
                'metadata' => [
                    'sort' => 30,
                ],
            ],
        ];

        $this->assertEquals($expected, $result);
    }

    /**
     * @dataProvider dataProvider_multiLevelArray
     *
     * @param array $dataset
     */
    public function testOrderFilterDescNestedArray($dataset): void
    {
        $orderFilter = new OrderFilter();
        $result = $orderFilter($dataset, 'metadata.sort', 'DESC');
        $expected =
        $expected = [
            [
                'name' => 'Order of Bacon',
                'metadata' => [
                    'sort' => 30,
                ],
            ],
            [
                'name' => 'Side order of fries',
                'metadata' => [
                    'sort' => 3,
                ],
            ],
            [
                'name' => 'Whee',
                'metadata' => [
                    'sort' => 0,
                ],
            ],
        ];

        $this->assertEquals($expected, $result);
    }

    public static function keyProvider(): iterable
    {
        return [
            ['page_count'],
            ['publisher'],
        ];
    }

    /**
     * @dataProvider keyProvider
     */
    public function testOrderFilterOnFrontMatterNumericAsc(mixed $sortKey): void
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
     */
    public function testOrderFilterOnFrontMatterNumericDesc(mixed $sortKey): void
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

    public function testOrderFilterNonArray(): void
    {
        $myString = 'Hello World';
        $orderFilter = new OrderFilter();
        $results = $orderFilter($myString, 'some-key');

        $this->assertEquals($myString, $results);
    }
}
