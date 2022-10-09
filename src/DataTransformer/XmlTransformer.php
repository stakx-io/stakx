<?php declare(strict_types=1);

/**
 * @copyright 2018 Vladimir Jimenez
 * @license   https://github.com/stakx-io/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\DataTransformer;

use allejo\stakx\Exception\DependencyMissingException;

class XmlTransformer implements DataTransformerInterface
{
    /**
     * {@inheritdoc}
     */
    public static function transformData($content): array
    {
        if (!function_exists('simplexml_load_string')) {
            throw new DependencyMissingException('XML', 'XML support is not available with the current PHP installation.');
        }

        libxml_use_internal_errors(true);
        $data = json_decode(json_encode(simplexml_load_string($content), JSON_THROW_ON_ERROR), true, 512, JSON_THROW_ON_ERROR);

        if ($data === false || $data === null) {
            return [];
        }

        return $data;
    }

    /**
     * {@inheritdoc}
     */
    public static function getExtensions(): array
    {
        return [
            'xml',
        ];
    }
}
