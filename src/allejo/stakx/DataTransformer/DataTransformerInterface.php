<?php

/**
 * @copyright 2017 Vladimir Jimenez
 * @license   https://github.com/allejo/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\DataTransformer;

interface DataTransformerInterface
{
    const NAME = 'stakx.data_transformer';

    /**
     * Transform content to an associative array.
     *
     * @param  string $content
     *
     * @return array
     */
    public static function transformData($content);

    /**
     * The file extensions this transformer will be applied to.
     *
     * @return array
     */
    public static function getExtensions();
}
