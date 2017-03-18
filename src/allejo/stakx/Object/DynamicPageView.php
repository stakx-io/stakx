<?php

/**
 * @copyright 2017 Vladimir Jimenez
 * @license   https://github.com/allejo/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Object;

class DynamicPageView extends PageView
{
    /**
     * The Content Items that belong to this Page View. This array will only have elements if it is a dynamic Page View.
     *
     * @var ContentItem[]
     */
    private $contentItems;

    /**
     * {@inheritdoc}
     */
    public function __construct($filePath)
    {
        parent::__construct($filePath);

        $this->contentItems = array();
        $this->type = PageView::DYNAMIC_TYPE;
    }

    /**
     * Add a ContentItem to this Dynamic PageView
     *
     * @param ContentItem $contentItem
     */
    public function addContentItem (&$contentItem)
    {
        $filePath = $this->fs->getRelativePath($contentItem->getFilePath());

        $this->contentItems[$filePath] = &$contentItem;
        $contentItem->setPageView($this);
    }

    /**
     * Get all of the ContentItems that belong to this Dynamic PageView
     *
     * @return ContentItem[]
     */
    public function getContentItems ()
    {
        return $this->contentItems;
    }

    /**
     * Get the collection name this dynamic PageView is charged with
     *
     * @return string
     */
    public function getCollection ()
    {
        return $this->getFrontMatter(false)['collection'];
    }
}