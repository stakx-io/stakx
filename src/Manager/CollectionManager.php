<?php declare(strict_types=1);

/**
 * @copyright 2018 Vladimir Jimenez
 * @license   https://github.com/stakx-io/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Manager;

use allejo\stakx\Configuration;
use allejo\stakx\Document\ContentItem;
use allejo\stakx\Document\JailedDocument;
use allejo\stakx\Event\CollectionDefinitionAdded;
use allejo\stakx\Event\CollectionItemAdded;
use allejo\stakx\Exception\TrackedItemNotFoundException;
use allejo\stakx\Filesystem\File;
use allejo\stakx\Filesystem\FileExplorer;
use allejo\stakx\Filesystem\FileExplorerDefinition;
use allejo\stakx\Filesystem\Folder;
use allejo\stakx\MarkupEngine\MarkupEngineManager;
use allejo\stakx\Templating\TemplateBridgeInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * The class that reads and saves information about all of the collections.
 */
class CollectionManager extends TrackingManager
{
    /** @var string[][] A copy of the collection definitions to be available for later usage. */
    private array $collectionDefinitions;

    /**
     * CollectionManager constructor.
     */
    public function __construct(private readonly MarkupEngineManager $markupEngineManager, private readonly Configuration $configuration, private readonly TemplateBridgeInterface $templateBridge, private readonly EventDispatcherInterface $eventDispatcher, private readonly LoggerInterface $logger)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function compileManager(): void
    {
        if (!$this->configuration->hasCollections()) {
            $this->logger->notice('No Collections defined... Ignoring');

            return;
        }

        $this->parseCollections($this->configuration->getCollectionsFolders());
    }

    /**
     * Get all of the ContentItems grouped by Collection name.
     *
     * ```php
     * [
     *     'collection name' => [
     *         new ContentItem(),
     *         new ContentItem(),
     *     ]
     * ]
     * ```
     *
     * @return ContentItem[][]
     */
    public function &getCollections(): array
    {
        return $this->trackedItems;
    }

    /**
     * Get a ContentItem from a Collection passed on it's path.
     *
     * @param string $filePath
     *
     * @throws TrackedItemNotFoundException
     */
    public function &getContentItem($filePath): ContentItem
    {
        if (!isset($this->trackedItemsFlattened[$filePath])) {
            throw new TrackedItemNotFoundException("The ContentItem at '{$filePath}' was not found.");
        }

        return $this->trackedItemsFlattened[$filePath];
    }

    /**
     * A jailed representation of CollectionManager::getCollections().
     *
     * @return JailedDocument[][]
     */
    public function getJailedCollections(): array
    {
        return self::getJailedTrackedItems($this->trackedItemsFlattened, fn ($contentItem) => $contentItem['basename']);
    }

    /**
     * Parse every collection and store them in the manager.
     *
     * @param string[][] $collections An array of definitions for collections
     */
    public function parseCollections($collections): void
    {
        if ($collections == null || empty($collections)) {
            $this->logger->debug('No collections found, nothing to parse.');

            return;
        }

        $this->collectionDefinitions = $collections;

        /**
         * The information which each collection has taken from the configuration file.
         *
         * $collection['name']   string The name of the collection
         *            ['folder'] string The folder where this collection has its ContentItems
         */
        foreach ($collections as $collection) {
            $this->logger->notice('Loading "{name}" collection...', [
                'name' => $collection['name'],
            ]);

            $folder = new Folder($collection['folder']);

            $event = new CollectionDefinitionAdded($collection['name'], $folder);
            $this->eventDispatcher->dispatch($event, CollectionDefinitionAdded::NAME);

            // Only fetch ContentItems with supported extensions
            $def = new FileExplorerDefinition($folder);
            $def->flags |= FileExplorer::INCLUDE_ONLY_FILES;
            $def->includes = [
                sprintf('/\.(%s)$/', implode('|', $this->markupEngineManager->getSupportedExtensions())),
            ];

            $this->declareTrackingNamespace($collection['name']);
            $this->scanTrackableItems($def, [
                'namespace' => $collection['name'],
            ]);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function createNewItem($filePath): mixed
    {
        $collection = $this->getCollectionNameFromPath($filePath);

        return $this->handleTrackableItem($filePath, [
            'namespace' => $collection,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function refreshItem($filePath): mixed
    {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    protected function handleTrackableItem(File $filePath, array $options = []): mixed
    {
        $collectionName = $options['namespace'];

        $contentItem = new ContentItem($filePath);
        $contentItem->setTemplateEngine($this->templateBridge);
        $contentItem->setMarkupEngine($this->markupEngineManager);
        $contentItem->setNamespace($collectionName);
        $contentItem->evaluateFrontMatter([], [
            'site' => $this->configuration->getConfiguration(),
        ]);

        $this->addObjectToTracker($contentItem, $collectionName);

        $this->logger->info('Loading ContentItem into "{name}" collection: {path}', [
            'name' => $collectionName,
            'path' => $filePath->getRelativeFilePath(),
        ]);

        $event = new CollectionItemAdded($contentItem);
        $this->eventDispatcher->dispatch($event, CollectionItemAdded::NAME);

        return $contentItem;
    }

    /**
     * Get the name of the Collection this ContentItem belongs to based on its location.
     */
    private function getCollectionNameFromPath(File $file): string
    {
        $folders = array_column($this->collectionDefinitions, 'folder');
        $index = array_search($file->getRelativeParentFolder(), $folders);

        if (isset($this->collectionDefinitions[$index]['name'])) {
            return $this->collectionDefinitions[$index]['name'];
        }

        return '';
    }
}
