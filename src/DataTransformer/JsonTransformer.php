<?php

/**
 * @copyright 2018 Vladimir Jimenez
 * @license   https://github.com/stakx-io/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\DataTransformer;

class JsonTransformer implements DataTransformerInterface
{
    public static function transformData(string $content): array
    {
        $data = json_decode($content, true);

        return $data ?? [];
    }

    public static function getExtensions(): array
    {
        return [
            'json',
        ];
    }
}
