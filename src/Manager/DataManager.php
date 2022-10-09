<?php declare(strict_types=1);

/**
 * @copyright 2018 Vladimir Jimenez
 * @license   https://github.com/stakx-io/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Manager;

use allejo\stakx\Configuration;
use allejo\stakx\DataTransformer\DataTransformerManager;
use allejo\stakx\Document\DataItem;
use allejo\stakx\Event\DataItemAdded;
use allejo\stakx\Event\DataItemFolderAdded;
use allejo\stakx\Event\DatasetDefinitionAdded;
use allejo\stakx\Exception\DependencyMissingException;
use allejo\stakx\Exception\UnsupportedDataTypeException;
use allejo\stakx\Filesystem\File;
use allejo\stakx\Filesystem\FileExplorerDefinition;
use allejo\stakx\Filesystem\Folder;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * This class handles everything in regards to DataItems and DataSets.
 */
class DataManager extends TrackingManager
{
    /**
     * DataManager constructor.
     */
    public function __construct(private readonly DataTransformerManager $dataTransformerManager, private readonly Configuration $configuration, private readonly EventDispatcherInterface $eventDispatcher, private readonly LoggerInterface $logger)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function compileManager(): void
    {
        if (!$this->configuration->hasDataItems()) {
            $this->logger->notice('No DataItems or Datasets detected... Ignoring.');

            return;
        }

        $this->parseDataItems($this->configuration->getDataFolders());
        $this->parseDataSets($this->configuration->getDataSets());
    }

    /**
     * Get all of the DataItems and DataSets in this manager.
     */
    public function &getDataItems(): array
    {
        return $this->trackedItems;
    }

    /**
     * {@inheritdoc}
     */
    public function getJailedDataItems()
    {
        return self::getJailedTrackedItems($this->trackedItemsFlattened, fn (DataItem $dataItem) => $dataItem->getBasename());
    }

    /**
     * Loop through all of the DataItems specified in `$folders`. Each folder will have contain just DataItems.
     *
     * For each folder, supported file type is read, parsed, and made available through `$this->getDataItems()`
     *
     * @param string[] $folders An array of folders to be searched for to contain DataItems
     */
    public function parseDataItems($folders): void
    {
        if ($folders === null) {
            return;
        }

        foreach ($folders as $folder) {
            $cls = new Folder($folder);

            $this->logger->debug('Scanning "{folder}" for data items...', [
                'folder' => $cls->getRelativeFilePath(),
            ]);

            $event = new DataItemFolderAdded($cls);
            $this->eventDispatcher->dispatch($event, DataItemFolderAdded::NAME);

            $def = new FileExplorerDefinition($cls);
            $this->scanTrackableItems($def);
        }
    }

    /**
     * Loop through all of the DataSets specified in `$dataSets`. Each DataSet contains a name and a folder location.
     *
     * For each folder, supported file type is read, parsed, and made available through `$this->getDataItems()`
     *
     * @param string[] $dataSets An array of DataSets
     */
    public function parseDataSets($dataSets): void
    {
        if ($dataSets === null) {
            return;
        }

        /**
         * The information which each DataSet has from the configuration file.
         *
         * @var array $dataSet = [
         *            'name' => '(string) The name of the collection',
         *            'folder' => '(string) The folder where this collection has its ContentItems'
         *            ]
         */
        foreach ($dataSets as $dataSet) {
            $folder = new Folder($dataSet['folder']);

            $this->logger->debug('Scanning "{folder}" for the "{name}" dataset...', [
                'folder' => $folder->getRelativeFilePath(),
                'name' => $dataSet['name'],
            ]);

            $event = new DatasetDefinitionAdded($dataSet['name'], $folder);
            $this->eventDispatcher->dispatch($event, DatasetDefinitionAdded::NAME);

            $def = new FileExplorerDefinition($folder);
            $this->declareTrackingNamespace($dataSet['name']);
            $this->scanTrackableItems($def, [
                'namespace' => $dataSet['name'],
            ]);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function handleTrackableItem(File $filePath, array $options = []): mixed
    {
        try {
            $namespace = $options['namespace'] ?? null;

            $dataItem = new DataItem($filePath);
            $dataItem->setDataTransformer($this->dataTransformerManager);
            $dataItem->setNamespace($namespace);

            $event = new DataItemAdded($dataItem);
            $this->eventDispatcher->dispatch($event, DataItemAdded::NAME);

            $this->addObjectToTracker($dataItem, $namespace);
            $this->saveTrackerOptions($dataItem->getRelativeFilePath(), $options);

            return $dataItem->getRelativeFilePath();
        } catch (DependencyMissingException $e) {
            if ($e->getDependency() === 'XML') {
                $this->logger->critical('XML support is not available in your PHP installation. For XML support, please install the appropriate package for your system:');
                $this->logger->critical('  e.g. php7.0-xml');
            }
        } catch (UnsupportedDataTypeException $e) {
            $this->logger->warning('There is no function to handle {ext} file format.', [
                'ext' => $e->getDataType(),
            ]);
        }

        return $filePath->getBasename();
    }
}
