<?php

/**
 * @copyright 2017 Vladimir Jimenez
 * @license   https://github.com/allejo/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Manager;

use allejo\stakx\Exception\DependencyMissingException;
use allejo\stakx\Document\DataItem;
use allejo\stakx\Exception\UnsupportedDataTypeException;
use allejo\stakx\Utilities\StrUtils;

/**
 * Class DataManager.
 *
 * This class handles everything in regards to DataItems and DataSets. This class supports reading the following data
 * types:
 *
 *   - CSV
 *   - JSON
 *   - XML
 *   - YAML
 */
class DataManager extends TrackingManager
{
    /**
     * Get all of the DataItems and DataSets in this manager.
     *
     * @return array
     */
    public function &getDataItems()
    {
        return $this->trackedItems;
    }

    /**
     * Loop through all of the DataItems specified in `$folders`. Each folder will have contain just DataItems.
     *
     * For each folder, supported file type is read, parsed, and made available through `$this->getDataItems()`
     *
     * @param string[] $folders An array of folders to be searched for to contain DataItems
     */
    public function parseDataItems($folders)
    {
        if ($folders === null)
        {
            return;
        }

        foreach ($folders as $folder)
        {
            $this->saveFolderDefinition($folder);
            $this->scanTrackableItems(
                $folder,
                array(),
                array(),
                array('/\.example$/')
            );
        }
    }

    /**
     * Loop through all of the DataSets specified in `$dataSets`. Each DataSet contains a name and a folder location.
     *
     * For each folder, supported file type is read, parsed, and made available through `$this->getDataItems()`
     *
     * @param string[] $dataSets An array of DataSets
     */
    public function parseDataSets($dataSets)
    {
        if ($dataSets === null)
        {
            return;
        }

        /**
         * The information which each DataSet has from the configuration file.
         *
         * $dataSet['name']   string The name of the collection
         *         ['folder'] string The folder where this collection has its ContentItems
         *
         * @var array
         */
        foreach ($dataSets as $dataSet)
        {
            $this->saveFolderDefinition($dataSet['folder'], array(
                'namespace' => $dataSet['name'],
            ));
            $this->scanTrackableItems(
                $dataSet['folder'],
                array('namespace' => $dataSet['name']),
                array(),
                array('/\.example$/')
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function handleTrackableItem($filePath, $options = array())
    {
        try
        {
            $namespace = (isset($options['namespace'])) ? $options['namespace'] : null;

            $dataItem = new DataItem($filePath);
            $dataItem->setNamespace($namespace);

            $this->saveTrackerOptions($dataItem->getRelativeFilePath(), $options);
            $this->addObjectToTracker($dataItem, $dataItem->getName(), $namespace);

            return $dataItem->getName();
        }
        catch (DependencyMissingException $e)
        {
            if ($e->getDependency() === 'XML')
            {
                $this->output->critical('XML support is not available in your PHP installation. For XML support, please install the appropriate package for your system:');
                $this->output->critical('  e.g. php7.0-xml');
            }
        }
        catch (UnsupportedDataTypeException $e)
        {
            $this->output->warning(StrUtils::interpolate('There is no function to handle {ext} file format.', array(
                'ext' => $e->getDataType()
            )));
        }

        return $this->fs->getBaseName($filePath);
    }
}
