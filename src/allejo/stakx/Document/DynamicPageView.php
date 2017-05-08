<?php

/**
 * @copyright 2017 Vladimir Jimenez
 * @license   https://github.com/allejo/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Document;

class DynamicPageView extends PageView
{
    /**
     * The Content Items that belong to this Page View. This array will only have elements if it is a dynamic Page View.
     *
     * @var ContentItem[]
     */
    private $repeatableItems;

    /**
     * {@inheritdoc}
     */
    public function __construct($filePath)
    {
        parent::__construct($filePath);

        $this->repeatableItems = array();
        $this->type = PageView::DYNAMIC_TYPE;
    }

    /**
     * Add a ContentItem to this Dynamic PageView.
     *
     * @param RepeatableItem $repeatableItem
     */
    public function addRepeatableItem(RepeatableItem &$repeatableItem)
    {
        $this->repeatableItems[$repeatableItem->getObjectName()] = &$repeatableItem;
        $repeatableItem->setPageView($this);
    }

    /**
     * Get all of the ContentItems that belong to this Dynamic PageView.
     *
     * @return ContentItem[]
     */
    public function getRepeatableItems()
    {
        return $this->repeatableItems;
    }

    /**
     * Get the collection name this dynamic PageView is charged with.
     *
     * @return string
     */
    public function getCollection()
    {
        return $this->getFrontMatter(false)['collection'];
    }

    /**
     * Get the dataset name this dynamic PageView is charged with.
     *
     * @return string
     */
    public function getDataset()
    {
        return $this->getFrontMatter(false)['dataset'];
    }

    public function getRepeatableName()
    {
        $fm = $this->getFrontMatter(false);

        if (isset($fm['collection']))
        {
            return $fm['collection'];
        }

        return $fm['dataset'];
    }

    public function getObjectName()
    {
        return $this->getRepeatableName();
    }
}
