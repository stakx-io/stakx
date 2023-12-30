<?php

/**
 * @copyright 2018 Vladimir Jimenez
 * @license   https://github.com/stakx-io/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\DataTransformer;

use Symfony\Component\Yaml\Yaml;

class YamlTransformer implements DataTransformerInterface
{
    public static function transformData(string $content): array
    {
        try
        {
            $data = Yaml::parse($content, Yaml::PARSE_DATETIME);
        }
        catch (\Exception $e)
        {
            return [];
        }

        if ($data === null)
        {
            return [];
        }

        return $data;
    }

    public static function getExtensions(): array
    {
        return [
            'yml',
            'yaml',
        ];
    }
}
