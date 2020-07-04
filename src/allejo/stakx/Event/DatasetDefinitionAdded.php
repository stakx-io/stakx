<?php

/**
 * @copyright 2018 Vladimir Jimenez
 * @license   https://github.com/stakx-io/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Event;

use allejo\stakx\Filesystem\Folder;
use Symfony\Component\EventDispatcher\Event;

/**
 * A notification-only event for when a Dataset definition is scanned.
 *
 * @since 0.2.0
 */
class DatasetDefinitionAdded extends Event
{
    const NAME = 'dataset.definition.added';

    private $datasetName;
    private $datasetFolder;

    public function __construct($datasetName, Folder $datasetFolder)
    {
        $this->datasetName = $datasetName;
        $this->datasetFolder = $datasetFolder;
    }

    /**
     * @return string
     */
    public function getDatasetName()
    {
        return $this->datasetName;
    }

    /**
     * @return Folder
     */
    public function getDatasetFolder()
    {
        return $this->datasetFolder;
    }
}
