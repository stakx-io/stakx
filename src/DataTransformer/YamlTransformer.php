<?php declare(strict_types=1);

/**
 * @copyright 2018 Vladimir Jimenez
 * @license   https://github.com/stakx-io/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\DataTransformer;

use Exception;
use Symfony\Component\Yaml\Yaml;

class YamlTransformer implements DataTransformerInterface
{
    /**
     * {@inheritdoc}
     */
    public static function transformData($content): array
    {
        try {
            $data = Yaml::parse($content, Yaml::PARSE_DATETIME);
        } catch (Exception) {
            return [];
        }

        if ($data === null) {
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
            'yml',
            'yaml',
        ];
    }
}
