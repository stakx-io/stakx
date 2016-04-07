<?php

/**
 * This file contains the DataManager class
 *
 * This file is part of the Stakx project.
 *
 * @copyright 2016 Vladimir Jimenez
 * @license   https://github.com/allejo/stakx/blob/master/LICENSE.md
 */

namespace allejo\stakx\Object;

use allejo\stakx\Environment\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Yaml\Yaml;

/**
 * Class DataManager
 *
 * This class handles everything in regards to DataItems and DataSets. This class supports reading the following data
 * types:
 *
 *   - CSV
 *   - JSON
 *   - XML
 *   - YAML
 *
 * @package allejo\stakx\Object
 */
class DataManager
{
    /**
     * @var array
     */
    protected $dataItems;

    /**
     * @var \allejo\stakx\Environment\Filesystem
     */
    protected $fs;

    /**
     * DataManager constructor.
     */
    public function __construct ()
    {
        $this->fs = new Filesystem();
    }

    /**
     * Get all of the DataItems and DataSets in this manager
     *
     * @return array
     */
    public function getDataItems ()
    {
        return $this->dataItems;
    }

    /**
     * Loop through all of the data folders specified in `$folders`.
     *
     * For each folder, supported file type is read, parsed, and made available through `$this->getDataItems()`
     *
     * @param string[] $folders
     * @param string[] $messages
     */
    public function parseDataItems ($folders, &$messages)
    {
        foreach ($folders as $folder)
        {
            $finder = new Finder();
            $finder->files()
                   ->ignoreDotFiles(true)
                   ->ignoreUnreadableDirs()
                   ->in($this->fs->absolutePath($folder));

            foreach ($finder as $dataItem)
            {
                $ext     = strtolower($this->fs->getExtension($dataItem));
                $name    = $this->fs->getBaseName($dataItem);
                $content = file_get_contents($dataItem);
                $fxnName = 'from' . ucfirst($ext);

                if (method_exists(get_called_class(), $fxnName))
                {
                    $this->dataItems[$name] = $this->$fxnName($content);
                }
                else
                {
                    $messages[] = array(0, "There is no function to handle '$ext' file format.");
                }
            }
        }
    }

    /**
     * Convert from CSV into an associative array
     *
     * @param  string $content CSV formatted text
     *
     * @return array
     */
    private function fromCsv ($content)
    {
        $rows    = array_map("str_getcsv", explode("\n", trim($content)));
        $columns = array_shift($rows);
        $csv     = array();

        foreach ($rows as $row)
        {
            $csv[] = array_combine($columns, $row);
        }

        return $csv;
    }

    /**
     * Convert from JSON into an associative array
     *
     * @param  string $content JSON formatted text
     *
     * @return array
     */
    private function fromJson ($content)
    {
        return json_decode($content, true);
    }

    /**
     * Convert from XML into an associative array
     *
     * @param  string $content XML formatted text
     *
     * @return array
     */
    private function fromXml ($content)
    {
        return json_decode(json_encode(simplexml_load_string($content)), true);
    }

    /**
     * Convert from YAML into an associative array
     *
     * @param  string $content YAML formatted text
     *
     * @return array
     */
    private function fromYaml ($content)
    {
        return Yaml::parse($content);
    }
}