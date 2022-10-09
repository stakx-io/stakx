<?php

/**
 * @copyright 2018 Vladimir Jimenez
 * @license   https://github.com/stakx-io/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Document;

use allejo\stakx\Filesystem\File;

class DynamicPageView extends BasePageView
{
    /** @var CollectableItem[] */
    private $collectableItems = [];

    /**
     * DynamicPageView constructor.
     */
    public function __construct(File $file)
    {
        parent::__construct($file);

        $this->type = BasePageView::DYNAMIC_TYPE;
    }

    /**
     * {@inheritdoc}
     */
    public function getIndexName()
    {
        return $this->getCollectableNamespace();
    }

    /**
     * Add a CollectableItem for this PageView to handle.
     */
    public function addCollectableItem(CollectableItem &$collectable)
    {
        $this->collectableItems[$collectable->getRelativeFilePath()] = &$collectable;
        $collectable->saveParentPageView($this);
    }

    /**
     * Delete a CollectableItem from this PageView.
     */
    public function delCollectableItem(CollectableItem &$collectableItem)
    {
        unset($this->collectableItems[$collectableItem->getRelativeFilePath()]);
    }

    /**
     * Get the Collectable Item based on its relative file path.
     *
     * @param string $relativeFilePath
     *
     * @return CollectableItem|TemplateReadyDocument|ReadableDocument|null
     */
    public function &getCollectableItem($relativeFilePath)
    {
        if (!$this->hasCollectableItem($relativeFilePath))
        {
            return null;
        }

        return $this->collectableItems[$relativeFilePath];
    }

    /**
     * Check whether or not this DynamicPageView manages a Collectable Item based on its relative path.
     *
     * @param string $relativeFilePath
     *
     * @return bool
     */
    public function hasCollectableItem($relativeFilePath)
    {
        return isset($this->collectableItems[$relativeFilePath]);
    }

    /**
     * Get all of the CollectableItems handled by this PageView.
     *
     * @return CollectableItem[]|TemplateReadyDocument[]|ReadableDocument[]
     */
    public function getCollectableItems()
    {
        return $this->collectableItems;
    }

    /**
     * Get the namespace this PageView is handling.
     *
     * @return string
     */
    public function getCollectableNamespace()
    {
        $fm = $this->getRawFrontMatter();

        if (isset($fm['collection']))
        {
            return $fm['collection'];
        }

        return $fm['dataset'];
    }
}
