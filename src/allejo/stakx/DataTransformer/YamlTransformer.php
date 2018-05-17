<?php

/**
 * @copyright 2018 Vladimir Jimenez
 * @license   https://github.com/stakx-io/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\DataTransformer;

use Symfony\Component\Yaml\Yaml;

class YamlTransformer implements DataTransformer
{
    /**
     * {@inheritdoc}
     */
    public static function transformData($content)
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
