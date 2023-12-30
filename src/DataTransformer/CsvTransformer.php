<?php

/**
 * @copyright 2018 Vladimir Jimenez
 * @license   https://github.com/stakx-io/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\DataTransformer;

class CsvTransformer implements DataTransformerInterface
{
    public static function transformData(string $content): array
    {
        $rows = array_map('str_getcsv', explode("\n", trim($content)));
        $columns = array_shift($rows);
        $csv = [];

        foreach ($rows as $row)
        {
            $csv[] = array_combine($columns, $row);
        }

        return $csv;
    }

    public static function getExtensions(): array
    {
        return [
            'csv',
        ];
    }
}
