<?php

/**
 * @copyright 2018 Vladimir Jimenez
 * @license   https://github.com/stakx-io/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\DataTransformer;

class JsonTransformer implements DataTransformer
{
    /**
     * {@inheritdoc}
     */
    public static function transformData($content)
    {
        $data = json_decode($content, true);

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
            'json',
        ];
    }
}
