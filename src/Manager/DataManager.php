<?php

/**
 * This file contains the DataManager class
 *
 * This file is part of the Stakx project.
 *
 * @copyright 2016 Vladimir Jimenez
 * @license   https://github.com/allejo/stakx/blob/master/LICENSE.md
 */

namespace allejo\stakx\Manager;

use allejo\stakx\Exception\DependencyMissingException;
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
class DataManager extends ItemManager
{
    /**
     * @var array
     */
    protected $dataItems;

    /**
     * DataManager constructor.
     */
    public function __construct ()
    {
        parent::__construct();

        $this->dataItems = array();
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
     * Loop through all of the DataItems specified in `$folders`. Each folder will have contain just DataItems.
     *
     * For each folder, supported file type is read, parsed, and made available through `$this->getDataItems()`
     *
     * @param string[] $folders  An array of folders to be searched for to contain DataItems
     */
    public function parseDataItems ($folders)
    {
        if ($folders === null) { return; }

        foreach ($folders as $folder)
        {
            $this->dataItems = array_merge(
                $this->dataItems,
                $this->parseFinderFiles($folder)
            );
        }
    }

    /**
     * Loop through all of the DataSets specified in `$dataSets`. Each DataSet contains a name and a folder location
     *
     * For each folder, supported file type is read, parsed, and made available through `$this->getDataItems()`
     *
     * @param string[] $dataSets An array of DataSets
     */
    public function parseDataSets ($dataSets)
    {
        if ($dataSets === null) { return; }

        /**
         * The information which each DataSet has from the configuration file
         *
         * $dataSet['name']   string The name of the collection
         *         ['folder'] string The folder where this collection has its ContentItems
         *
         * @var $dataSet array
         */
        foreach ($dataSets as $dataSet)
        {
            $this->dataItems[$dataSet['name']] = $this->parseFinderFiles($dataSet['folder']);
        }
    }

    /**
     * Parse all of the data files in a specified folder
     *
     * @param string   $folder   A folder that contains data files
     *
     * @return array
     */
    private function parseFinderFiles ($folder)
    {
        $dataItems = array();
        $finder    = new Finder();
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
                $this->handleDependencies($ext);
                $dataItems[$name] = $this->$fxnName($content);
            }
            else
            {
                $this->output->warning("There is no function to handle '$ext' file format.");
            }
        }

        return $dataItems;
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

    /**
     * @param string $extension
     *
     * @todo 0.1.0 Create a help page on the main stakx website for this topic and link to it
     *
     * @throws DependencyMissingException
     */
    private function handleDependencies ($extension)
    {
        if ($extension === 'xml' && !function_exists('simplexml_load_string'))
        {
            $this->output->critical('XML support is not available in your PHP installation. For XML support, please install the appropriate package for your system:');
            $this->output->critical('  e.g. php7.0-xml');

            throw new DependencyMissingException('XML support is not available with the current PHP installation.');
        }
    }
}