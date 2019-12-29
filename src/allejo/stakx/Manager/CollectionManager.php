<?php

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
    private $collectionDefinitions;
    /** @var MarkupEngineManager */
    private $markupEngineManager;
    /** @var Configuration */
    private $configuration;
    /** @var EventDispatcherInterface */
    private $eventDispatcher;
    /** @var TemplateBridgeInterface */
    private $templateBridge;
    /** @var LoggerInterface */
    private $logger;

    /**
     * CollectionManager constructor.
     */
    public function __construct(
        MarkupEngineManager $markupEngineManager,
        Configuration $configuration,
        TemplateBridgeInterface $templateBridge,
        EventDispatcherInterface $eventDispatcher,
        LoggerInterface $logger
    ) {
        $this->markupEngineManager = $markupEngineManager;
        $this->configuration = $configuration;
        $this->eventDispatcher = $eventDispatcher;
        $this->templateBridge = $templateBridge;
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function compileManager()
    {
        if (!$this->configuration->hasCollections())
        {
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
    public function &getCollections()
    {
        return $this->trackedItems;
    }

    /**
     * Get a ContentItem from a Collection passed on it's path.
     *
     * @param string $filePath
     *
     * @throws TrackedItemNotFoundException
     *
     * @return ContentItem
     */
    public function &getContentItem($filePath)
    {
        if (!isset($this->trackedItemsFlattened[$filePath]))
        {
            throw new TrackedItemNotFoundException("The ContentItem at '$filePath' was not found.");
        }

        return $this->trackedItemsFlattened[$filePath];
    }

    /**
     * A jailed representation of CollectionManager::getCollections().
     *
     * @return JailedDocument[][]
     */
    public function getJailedCollections()
    {
        return self::getJailedTrackedItems($this->trackedItemsFlattened, function ($contentItem) {
            return $contentItem['basename'];
        });
    }

    /**
     * Parse every collection and store them in the manager.
     *
     * @param string[][] $collections An array of definitions for collections
     */
    public function parseCollections($collections)
    {
        if ($collections == null || empty($collections))
        {
            $this->logger->debug('No collections found, nothing to parse.');

            return;
        }

        $this->collectionDefinitions = $collections;

        /**
         * The information which each collection has taken from the configuration file.
         *
         * $collection['name']   string The name of the collection
         *            ['folder'] string The folder where this collection has its ContentItems
         *
         * @var array
         */
        foreach ($collections as $collection)
        {
            $this->logger->notice('Loading "{name}" collection...', [
                'name' => $collection['name'],
            ]);

            $folder = new Folder($collection['folder']);

            $event = new CollectionDefinitionAdded($collection['name'], $folder);
            $this->eventDispatcher->dispatch(CollectionDefinitionAdded::NAME, $event);

            // Only fetch ContentItems with supported extensions
            $def = new FileExplorerDefinition($folder);
            $def->flags |= FileExplorer::INCLUDE_ONLY_FILES;
            $def->includes = [
                sprintf('/\.(%s)$/', implode('|', $this->markupEngineManager->getSupportedExtensions())),
            ];

            $this->scanTrackableItems($def, [
                'namespace' => $collection['name'],
            ]);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function createNewItem($filePath)
    {
        $collection = $this->getCollectionNameFromPath($filePath);

        return $this->handleTrackableItem($filePath, [
            'namespace' => $collection,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function refreshItem($filePath)
    {
    }

    /**
     * {@inheritdoc}
     */
    protected function handleTrackableItem(File $filePath, array $options = [])
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
        $this->eventDispatcher->dispatch(CollectionItemAdded::NAME, $event);

        return $contentItem;
    }

    /**
     * Get the name of the Collection this ContentItem belongs to based on its location.
     *
     * @param File $file
     *
     * @return string
     */
    private function getCollectionNameFromPath(File $file)
    {
        $folders = array_column($this->collectionDefinitions, 'folder');
        $index = array_search($file->getRelativeParentFolder(), $folders);

        if (isset($this->collectionDefinitions[$index]['name']))
        {
            return $this->collectionDefinitions[$index]['name'];
        }

        return '';
    }
}
