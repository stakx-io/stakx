<?php declare(strict_types=1);

/**
 * @copyright 2018 Vladimir Jimenez
 * @license   https://github.com/stakx-io/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\DataTransformer;

class CsvTransformer implements DataTransformerInterface
{
    /**
     * {@inheritdoc}
     */
    public static function transformData($content): array
    {
        $rows = array_map('str_getcsv', explode("\n", trim($content)));
        $columns = array_shift($rows);
        $csv = [];

        foreach ($rows as $row) {
            $csv[] = array_combine($columns, $row);
        }

        return $csv;
    }

    /**
     * {@inheritdoc}
     */
    public static function getExtensions(): array
    {
        return [
            'csv',
        ];
    }
}
