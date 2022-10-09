<?php declare(strict_types=1);

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
    final public const NAME = 'dataset.definition.added';

    public function __construct(private $datasetName, private readonly Folder $datasetFolder)
    {
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
