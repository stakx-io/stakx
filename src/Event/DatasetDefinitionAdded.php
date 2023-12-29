<?php

/**
 * @copyright 2018 Vladimir Jimenez
 * @license   https://github.com/stakx-io/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Event;

use allejo\stakx\Filesystem\Folder;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * A notification-only event for when a Dataset definition is scanned.
 *
 * @since 0.2.0
 */
class DatasetDefinitionAdded extends Event
{
    public const NAME = 'dataset.definition.added';

    private string $datasetName;
    private Folder $datasetFolder;

    public function __construct($datasetName, Folder $datasetFolder)
    {
        $this->datasetName = $datasetName;
        $this->datasetFolder = $datasetFolder;
    }

    public function getDatasetName(): string
    {
        return $this->datasetName;
    }

    public function getDatasetFolder(): Folder
    {
        return $this->datasetFolder;
    }
}
