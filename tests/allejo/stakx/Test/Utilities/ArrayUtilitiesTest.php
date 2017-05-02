<?php

/**
 * @copyright 2017 Vladimir Jimenez
 * @license   https://github.com/allejo/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Test\Utilities;

use allejo\stakx\Utilities\ArrayUtilities;

class ArrayUtilitiesTest extends \PHPUnit_Framework_TestCase
{
    public static function dataProviderMultidimensionalArrays()
    {
        return array(
            array(
                array(1, 2, 3, 4, 5),
                false,
            ),
            array(
                array(array('name' => 1), 2, 3),
                true,
            ),
            array(
                array(1, 2, 3, array(3.25, 3.5, 3.75), 4, 5),
                true,
            )
        );
    }

    /**
     * @dataProvider dataProviderMultidimensionalArrays
     *
     * @param array $arr
     * @param       $result
     */
    public function testIsMultidimensional(array $arr, $result)
    {
        $this->assertEquals($result, ArrayUtilities::is_multidimensional($arr));
    }

    public static function dataProviderIndexedTestArrays()
    {
        return array(
            array(
                array(
                    array('name' => 'idx1', 'value' => 1),
                    array('name' => 'idx2', 'value' => 2),
                    array('name' => 'idx3', 'value' => 3),
                ),
                true,
                'name',
            ),
            array(
                array(
                    array('name' => 3, 'value' => 1),
                    array('name' => 4, 'value' => 2),
                    array('name' => 5, 'value' => 3),
                ),
                true,
                'name',
            ),
            array(
                array(
                    array('name' => array(1, 2, 3), 'value' => 1),
                    array('name' => 'idx2', 'value' => 2),
                    array('name' => 'idx3', 'value' => 3),
                ),
                true,
                'name',
            ),
            array(
                array(
                    array('nested' => array('name' => 'idx1', 'value' => 1)),
                    array('nested' => array('name' => 'idx2', 'value' => 2)),
                    array('nested' => array('name' => 'idx3', 'value' => 3)),
                ),
                false,
                'name',
            ),
            array(
                array(
                    array('toast' => 1),
                    array('toast' => 2),
                    array('toast' => 3),
                ),
                false,
                'name',
            ),
            array(
                array(
                    array(1, 2),
                    array(3, 4),
                    array(5, 6),
                ),
                false,
                'name',
            ),
        );
    }

    /**
     * @dataProvider dataProviderIndexedTestArrays
     *
     * @param array $arr
     * @param       $result
     * @param       $index
     */
    public function testArrayCanBeIndexed(array $arr, $result, $index)
    {
        $this->assertEquals($result, ArrayUtilities::array_can_be_indexed($arr, $index));
    }

    public static function dataProviderIndexableArrays()
    {
        return array(
            array(
                array(
                    array('name' => 'idx1', 'value' => 1),
                    array('name' => 'idx2', 'value' => 2),
                    array('name' => 'idx3', 'value' => 3),
                ),
                array(
                    'idx1' => array('name' => 'idx1', 'value' => 1),
                    'idx2' => array('name' => 'idx2', 'value' => 2),
                    'idx3' => array('name' => 'idx3', 'value' => 3),
                ),
                'name',
            ),
            array(
                array(
                    array('name' => 3, 'value' => 1),
                    array('name' => 4, 'value' => 2),
                    array('name' => 5, 'value' => 3),
                ),
                array(
                    3 => array('name' => 3, 'value' => 1),
                    4 => array('name' => 4, 'value' => 2),
                    5 => array('name' => 5, 'value' => 3),
                ),
                'name',
            ),
            array(
                array(
                    array('name' => array(1, 2, 3), 'value' => 1),
                    array('name' => 'idx2', 'value' => 2),
                    array('name' => 'idx3', 'value' => 3),
                ),
                array(
                    'idx2' => array('name' => 'idx2', 'value' => 2),
                    'idx3' => array('name' => 'idx3', 'value' => 3),
                ),
                'name',
            ),
        );
    }

    /**
     * @dataProvider dataProviderIndexableArrays
     *
     * @param array $arr
     * @param       $result
     * @param       $index
     */
    public function testArrayIndexByKey(array $arr, $result, $index)
    {
        $this->assertEquals($result, ArrayUtilities::array_index_by_key($arr, $index));
    }

    public static function dataProviderMergeDefaultsArrays()
    {
        return array(
            array(
                array(
                    'string' => 'overwrite',
                    'number' => 20,
                    'array1' => 'secret1',
                    'array2' => 'secret2',
                ),
                array(
                    'string' => 'original',
                    'number' => 10,
                    'array1' => 'secret1',
                ),
                array(
                    'string' => 'overwrite',
                    'number' => 20,
                    'array2' => 'secret2',
                ),
                'name'
            ),
            array(
                array(
                    'array' => array(1, 2, 3, 4, 5, 6),
                ),
                array(
                    'array' => array(1, 2, 3),
                ),
                array(
                    'array' => array(4, 5, 6),
                ),
                'name'
            ),
            array(
                array(
                    'bool' => false,
                    'index-me' => array(
                        'idx1' => array(
                            'name' => 'idx1',
                            'value' => 1
                        ),
                        'idx2' => array(
                            'name' => 'idx2',
                            'value' => 2,
                        ),
                        'idx3' => array(
                            'name' => 'idx3',
                            'value' => 3,
                        ),
                    ),
                ),
                array(
                    'bool' => true,
                    'index-me' => array(
                        array(
                            'name' => 'idx1',
                            'value' => 1,
                        ),
                        array(
                            'name' => 'idx2',
                            'value' => 100,
                        ),
                    )
                ),
                array(
                    'bool' => false,
                    'index-me' => array(
                        array(
                            'name' => 'idx2',
                            'value' => 2,
                        ),
                        array(
                            'name' => 'idx3',
                            'value' => 3,
                        ),
                    ),
                ),
                'name'
            ),
        );
    }

    /**
     * @dataProvider dataProviderMergeDefaultsArrays
     *
     * @param array $result
     * @param array $arr1
     * @param array $arr2
     * @param       $indexKey
     */
    public function testArrayMergeDefaults(array $result, array $arr1, array $arr2, $indexKey)
    {
        $merged = ArrayUtilities::array_merge_defaults($arr1, $arr2, $indexKey);

        foreach ($merged as $key => $value)
        {
            $this->assertEquals($result[$key], $value);
        }
    }

    public function testAssociativeArraySplit()
    {
        $array = array(
            'index1' => 1,
            'index2' => 2,
            'index3' => 3,
        );
        $result = array(
            array(
                'index1' => 1,
                'index2' => 2,
            ),
            array(
                'index3' => 3,
            )
        );

        $this->assertEquals($result, ArrayUtilities::associative_array_split('index2', $array));
    }

    public function testAssociativeArraySplitNotInclusive()
    {
        $array = array(
            'index1' => 1,
            'index2' => 2,
            'index3' => 3,
        );
        $result = array(
            array(
                'index1' => 1,
            ),
            array(
                'index2' => 2,
                'index3' => 3,
            )
        );

        $this->assertEquals($result, ArrayUtilities::associative_array_split('index2', $array, false));
    }
}
