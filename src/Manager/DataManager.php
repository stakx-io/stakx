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
class DataManager extends TrackingManager
{
    /**
     * Get all of the DataItems and DataSets in this manager
     *
     * @return array
     */
    public function getDataItems ()
    {
        return $this->trackedItems;
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
            $this->saveFolderDefinition($folder);
            $this->scanTrackableItems($folder);
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
            $this->saveFolderDefinition($dataSet['folder'], array(
                'namespace' => $dataSet['name']
            ));
            $this->scanTrackableItems($dataSet['folder'], array(
                'namespace' => $dataSet['name']
            ));
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function handleTrackableItem ($filePath, $options = array())
    {
        $relFilePath = $this->fs->getRelativePath($filePath);
        $ext     = strtolower($this->fs->getExtension($filePath));
        $name    = $this->fs->getBaseName($filePath);
        $content = file_get_contents($filePath);
        $fxnName = 'from' . ucfirst($ext);

        if (method_exists(get_called_class(), $fxnName))
        {
            $this->handleDependencies($ext);
            $this->saveTrackerOptions($relFilePath, $options);
            $this->addArrayToTracker(
                $name,
                $this->$fxnName($content),
                $relFilePath,
                (array_key_exists('namespace', $options)) ? $options['namespace'] : null
            );
        }
        else
        {
            $this->output->warning("There is no function to handle '$ext' file format.");
        }

        return $name;
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
        return Yaml::parse($content, Yaml::PARSE_DATETIME);
    }

    /**
     * An alias for handling `*.yml` files
     *
     * @param  string $content YAML formatted text
     *
     * @return array
     */
    private function fromYml ($content)
    {
        return $this->fromYaml($content);
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