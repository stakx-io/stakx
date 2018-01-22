<?php

/**
 * @copyright 2017 Vladimir Jimenez
 * @license   https://github.com/allejo/stakx/blob/master/LICENSE.md MIT
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
        $collectable->setParentPageView($this);
    }

    /**
     * Delete a CollectableItem from this PageView.
     */
    public function delCollectableItem(CollectableItem &$collectableItem)
    {
        unset($this->collectableItems[$collectableItem->getRelativeFilePath()]);
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
