<?php

/**
 * @copyright 2018 Vladimir Jimenez
 * @license   https://github.com/stakx-io/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\EventSubscriber;

use allejo\stakx\Event\CollectionDefinitionAdded;
use allejo\stakx\Event\CollectionItemAdded;
use allejo\stakx\Event\CompileProcessTemplateCreation;
use allejo\stakx\Event\DataItemAdded;
use allejo\stakx\Event\DataItemFolderAdded;
use allejo\stakx\Event\DatasetDefinitionAdded;
use allejo\stakx\Event\PageViewAdded;
use allejo\stakx\Event\PageViewDefinitionAdded;
use allejo\stakx\FileMapper;
use allejo\stakx\Filesystem\FilesystemLoader as fs;
use allejo\stakx\Filesystem\FilesystemPath;
use allejo\stakx\Manager\ThemeManager;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class WatchSubscriber implements EventSubscriberInterface
{
    private $fileMapper;

    public function __construct(FileMapper $fileMapper)
    {
        $this->fileMapper = $fileMapper;
    }

    public function recordCollectionDefinition(CollectionDefinitionAdded $event)
    {
        $this->fileMapper->registerFolder($event->getFolderName(), FileMapper::CONTENT_ITEM);
        $this->fileMapper->registerMetadata(
            sprintf('collection.%s', $event->getCollectionName()),
            $event->getFolderName()
        );
    }

    public function recordCollectionItem(CollectionItemAdded $event)
    {
        $this->fileMapper->registerFile($event->getContentItem());
    }

    public function recordDataItemDefinition(DataItemFolderAdded $event)
    {
        $this->fileMapper->registerFolder($event->getFolderName(), FileMapper::DATA_ITEM);
    }

    public function recordDataItem(DataItemAdded $event)
    {
        $this->fileMapper->registerFile($event->getDataItem());
    }

    public function recordDatasetDefinition(DatasetDefinitionAdded $event)
    {
        $this->fileMapper->registerFolder($event->getDatasetFolder(), FileMapper::DATA_ITEM);
        $this->fileMapper->registerMetadata(
            sprintf('dataset.%s', $event->getDatasetName()),
            $event->getDatasetFolder()
        );
    }

    public function recordPageView(PageViewAdded $event)
    {
        $this->fileMapper->registerFile($event->getPageView());
    }

    public function recordPageViewDefinition(PageViewDefinitionAdded $event)
    {
        $this->fileMapper->registerFolder($event->getFolderName(), FileMapper::BASE_PAGEVIEW);
    }

    public function recordTemplateDependencies(CompileProcessTemplateCreation $event)
    {
        $pageView = $event->getPageView();
        $template = $event->getTemplate();

        // Keep track of import dependencies
        foreach ($pageView->getImportDependencies() as $dependency)
        {
            $this->fileMapper->registerTemplateInclude($dependency, $pageView->getRelativeFilePath());
        }

        // Keep track of Twig extends'
        $parent = $template->getParentTemplate();

        while ($parent !== false)
        {
            // Replace the '@theme' namespace in Twig with the path to the theme folder and create a FilesystemPath object from the given path
            $path = str_replace('@theme', fs::appendPath(ThemeManager::THEME_FOLDER, $event->getTheme()), $parent->getTemplateName());
            $path = new FilesystemPath($path);

            $this->fileMapper->registerTemplateExtend((string)$path, $pageView->getRelativeFilePath());

            $parent = $parent->getParentTemplate();
        }
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        // @TODO Don't subscribe to these events unless we're in "watch" mode.

        return [
            CollectionDefinitionAdded::NAME => 'recordCollectionDefinition',
            CollectionItemAdded::NAME => 'recordCollectionItem',

            DataItemFolderAdded::NAME => 'recordDataItemDefinition',
            DataItemAdded::NAME => 'recordDataItem',
            DatasetDefinitionAdded::NAME => 'recordDatasetDefinition',

            PageViewAdded::NAME => 'recordPageView',
            PageViewDefinitionAdded::NAME => 'recordPageViewDefinition',

            CompileProcessTemplateCreation::NAME => 'recordTemplateDependencies',
        ];
    }
}
