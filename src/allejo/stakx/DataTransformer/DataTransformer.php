<?php

/**
 * @copyright 2017 Vladimir Jimenez
 * @license   https://github.com/allejo/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\DataTransformer;

/**
 * The definition for transforming data formats supported in DataItems.
 */
interface DataTransformer
{
    const CONTAINER_TAG = 'stakx.data_transformer';

    /**
     * Transform content to an associative array.
     *
     * @param string $content The content to be converted in an array.
     *
     * @return array
     */
    public static function transformData($content);

    /**
     * The lowercase file extensions this transformer will be applied to.
     *
     * @return array
     */
    public static function getExtensions();
}
