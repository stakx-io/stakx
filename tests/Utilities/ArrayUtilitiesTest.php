<?php declare(strict_types=1);

/**
 * @copyright 2018 Vladimir Jimenez
 * @license   https://github.com/stakx-io/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Test\Utilities;

use allejo\stakx\Utilities\ArrayUtilities;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
class ArrayUtilitiesTest extends TestCase
{
    public static function provideIsMultidimensionalCases(): iterable
    {
        return [
            [
                [1, 2, 3, 4, 5],
                false,
            ],
            [
                [['name' => 1], 2, 3],
                true,
            ],
            [
                [1, 2, 3, [3.25, 3.5, 3.75], 4, 5],
                true,
            ],
        ];
    }

    /**
     * @dataProvider provideIsMultidimensionalCases
     */
    public function testIsMultidimensional(array $arr, mixed $result): void
    {
        $this->assertEquals($result, ArrayUtilities::is_multidimensional($arr));
    }

    public static function provideArrayCanBeIndexedCases(): iterable
    {
        return [
            [
                [
                    ['name' => 'idx1', 'value' => 1],
                    ['name' => 'idx2', 'value' => 2],
                    ['name' => 'idx3', 'value' => 3],
                ],
                true,
                'name',
            ],
            [
                [
                    ['name' => 3, 'value' => 1],
                    ['name' => 4, 'value' => 2],
                    ['name' => 5, 'value' => 3],
                ],
                true,
                'name',
            ],
            [
                [
                    ['name' => [1, 2, 3], 'value' => 1],
                    ['name' => 'idx2', 'value' => 2],
                    ['name' => 'idx3', 'value' => 3],
                ],
                true,
                'name',
            ],
            [
                [
                    ['nested' => ['name' => 'idx1', 'value' => 1]],
                    ['nested' => ['name' => 'idx2', 'value' => 2]],
                    ['nested' => ['name' => 'idx3', 'value' => 3]],
                ],
                false,
                'name',
            ],
            [
                [
                    ['toast' => 1],
                    ['toast' => 2],
                    ['toast' => 3],
                ],
                false,
                'name',
            ],
            [
                [
                    [1, 2],
                    [3, 4],
                    [5, 6],
                ],
                false,
                'name',
            ],
        ];
    }

    /**
     * @dataProvider provideArrayCanBeIndexedCases
     */
    public function testArrayCanBeIndexed(array $arr, mixed $result, mixed $index): void
    {
        $this->assertEquals($result, ArrayUtilities::array_can_be_indexed($arr, $index));
    }

    public static function provideArrayIndexByKeyCases(): iterable
    {
        return [
            [
                [
                    ['name' => 'idx1', 'value' => 1],
                    ['name' => 'idx2', 'value' => 2],
                    ['name' => 'idx3', 'value' => 3],
                ],
                [
                    'idx1' => ['name' => 'idx1', 'value' => 1],
                    'idx2' => ['name' => 'idx2', 'value' => 2],
                    'idx3' => ['name' => 'idx3', 'value' => 3],
                ],
                'name',
            ],
            [
                [
                    ['name' => 3, 'value' => 1],
                    ['name' => 4, 'value' => 2],
                    ['name' => 5, 'value' => 3],
                ],
                [
                    3 => ['name' => 3, 'value' => 1],
                    4 => ['name' => 4, 'value' => 2],
                    5 => ['name' => 5, 'value' => 3],
                ],
                'name',
            ],
            [
                [
                    ['name' => [1, 2, 3], 'value' => 1],
                    ['name' => 'idx2', 'value' => 2],
                    ['name' => 'idx3', 'value' => 3],
                ],
                [
                    'idx2' => ['name' => 'idx2', 'value' => 2],
                    'idx3' => ['name' => 'idx3', 'value' => 3],
                ],
                'name',
            ],
        ];
    }

    /**
     * @dataProvider provideArrayIndexByKeyCases
     */
    public function testArrayIndexByKey(array $arr, mixed $result, mixed $index): void
    {
        $this->assertEquals($result, ArrayUtilities::array_index_by_key($arr, $index));
    }

    public static function provideArrayMergeDefaultsCases(): iterable
    {
        return [
            [
                [
                    'string' => 'overwrite',
                    'number' => 20,
                    'array1' => 'secret1',
                    'array2' => 'secret2',
                ],
                [
                    'string' => 'original',
                    'number' => 10,
                    'array1' => 'secret1',
                ],
                [
                    'string' => 'overwrite',
                    'number' => 20,
                    'array2' => 'secret2',
                ],
                'name',
            ],
            [
                [
                    'array' => [1, 2, 3, 4, 5, 6],
                ],
                [
                    'array' => [1, 2, 3],
                ],
                [
                    'array' => [4, 5, 6],
                ],
                'name',
            ],
            [
                [
                    'bool' => false,
                    'index-me' => [
                        'idx1' => [
                            'name' => 'idx1',
                            'value' => 1,
                        ],
                        'idx2' => [
                            'name' => 'idx2',
                            'value' => 2,
                        ],
                        'idx3' => [
                            'name' => 'idx3',
                            'value' => 3,
                        ],
                    ],
                ],
                [
                    'bool' => true,
                    'index-me' => [
                        [
                            'name' => 'idx1',
                            'value' => 1,
                        ],
                        [
                            'name' => 'idx2',
                            'value' => 100,
                        ],
                    ],
                ],
                [
                    'bool' => false,
                    'index-me' => [
                        [
                            'name' => 'idx2',
                            'value' => 2,
                        ],
                        [
                            'name' => 'idx3',
                            'value' => 3,
                        ],
                    ],
                ],
                'name',
            ],
        ];
    }

    /**
     * @dataProvider provideArrayMergeDefaultsCases
     */
    public function testArrayMergeDefaults(array $result, array $arr1, array $arr2, mixed $indexKey): void
    {
        $merged = ArrayUtilities::array_merge_defaults($arr1, $arr2, $indexKey);

        foreach ($merged as $key => $value) {
            $this->assertEquals($result[$key], $value);
        }
    }

    public function testAssociativeArraySplit(): void
    {
        $array = [
            'index1' => 1,
            'index2' => 2,
            'index3' => 3,
        ];
        $result = [
            [
                'index1' => 1,
                'index2' => 2,
            ],
            [
                'index3' => 3,
            ],
        ];

        $this->assertEquals($result, ArrayUtilities::associative_array_split('index2', $array));
    }

    public function testAssociativeArraySplitNotInclusive(): void
    {
        $array = [
            'index1' => 1,
            'index2' => 2,
            'index3' => 3,
        ];
        $result = [
            [
                'index1' => 1,
            ],
            [
                'index2' => 2,
                'index3' => 3,
            ],
        ];

        $this->assertEquals($result, ArrayUtilities::associative_array_split('index2', $array, false));
    }
}
