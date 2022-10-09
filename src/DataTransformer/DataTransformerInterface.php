<?php declare(strict_types=1);

/**
 * @copyright 2018 Vladimir Jimenez
 * @license   https://github.com/stakx-io/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\DataTransformer;

/**
 * The definition for transforming data formats supported in DataItems.
 */
interface DataTransformerInterface
{
    public const CONTAINER_TAG = 'stakx.data_transformer';

    /**
     * Transform content to an associative array.
     *
     * @param string $content the content to be converted in an array
     */
    public static function transformData(string $content): array;

    /**
     * The lowercase file extensions this transformer will be applied to.
     */
    public static function getExtensions(): array;
}
