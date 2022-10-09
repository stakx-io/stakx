<?php declare(strict_types=1);

/**
 * @copyright 2018 Vladimir Jimenez
 * @license   https://github.com/stakx-io/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\DataTransformer;

class JsonTransformer implements DataTransformerInterface
{
    /**
     * {@inheritdoc}
     */
    public static function transformData($content): array
    {
        try {
            return json_decode($content, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return [];
        }
    }

    /**
     * {@inheritdoc}
     */
    public static function getExtensions(): array
    {
        return [
            'json',
        ];
    }
}
