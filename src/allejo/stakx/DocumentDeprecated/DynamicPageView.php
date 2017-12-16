<?php

/**
 * @copyright 2017 Vladimir Jimenez
 * @license   https://github.com/allejo/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\DocumentDeprecated;

/**
 * A dynamic PageView is created when the following keywords are found in the FrontMatter of a PageView:
 *
 *   - collection
 *   - dataset
 *
 * This PageView type will contain references to all of the RepeatableItems
 */
class DynamicPageView extends PageView
{
    /**
     * The RepeatableItems that belong to this PageView.
     *
     * @var RepeatableItem[]
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
     * Add a RepeatableItem to this dynamic PageView.
     *
     * @param RepeatableItem $repeatableItem
     */
    public function addRepeatableItem(RepeatableItem &$repeatableItem)
    {
        $this->repeatableItems[$repeatableItem->getObjectName()] = &$repeatableItem;
        $repeatableItem->setParentPageView($this);
    }

    /**
     * Remove a RepeatableItem from the list of items that this dynamic PageView is responsible for.
     *
     * @param RepeatableItem $repeatableItem
     */
    public function delRepeatableItem(RepeatableItem &$repeatableItem)
    {
        unset($this->repeatableItems[$repeatableItem->getObjectName()]);
    }

    /**
     * Get all of the RepeatableItem that belong to this dynamic PageView.
     *
     * @return RepeatableItem[]|TwigDocument[]
     */
    public function getRepeatableItems()
    {
        return $this->repeatableItems;
    }

    public function getRepeatableNamespace()
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
        return $this->getRepeatableNamespace();
    }
}
