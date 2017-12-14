<?php
/**
 * @copyright 2017 Vladimir Jimenez
 * @license   https://github.com/allejo/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\DataTransformer;

use Symfony\Component\Yaml\Yaml;

class YamlTransformer implements DataTransformerInterface
{
    /**
     * {@inheritdoc}
     */
    public static function transformData($content)
    {
        $data = Yaml::parse($content, Yaml::PARSE_DATETIME);

        if ($data === null)
        {
            return [];
        }

        return $data;
    }

    /**
     * {@inheritdoc}
     */
    public static function getExtensions()
    {
        return [
            'yml',
            'yaml',
        ];
    }
}
