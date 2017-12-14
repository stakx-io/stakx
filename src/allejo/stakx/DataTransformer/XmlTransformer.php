<?php

/**
 * @copyright 2017 Vladimir Jimenez
 * @license   https://github.com/allejo/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\DataTransformer;

use allejo\stakx\Exception\DependencyMissingException;

class XmlTransformer implements DataTransformerInterface
{
    /**
     * {@inheritdoc}
     */
    public static function transformData($content)
    {
        if (!function_exists('simplexml_load_string'))
        {
            throw new DependencyMissingException('XML', 'XML support is not available with the current PHP installation.');
        }

        $data = json_decode(json_encode(simplexml_load_string($content)), true);

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
            'xml',
        ];
    }
}
