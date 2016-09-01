<?php

namespace allejo\stakx\Object;

class PageView extends FrontMatterObject
{
    /**
     * The Content Items that belong to this Page View. This array will only have elements if it is a dynamic Page View.
     *
     * @var ContentItem[]
     */
    private $contentItems;

    /**
     * @param ContentItem $contentItem
     */
    public function addContentItem (&$contentItem)
    {
        $filePath = $this->fs->getRelativePath($contentItem->getFilePath());

        $this->contentItems[$filePath] = &$contentItem;
        $contentItem->setPageView($this);
    }

    /**
     * @return string Twig body
     */
    public function getContent ()
    {
        return $this->bodyContent;
    }

    /**
     * Get all of the Content Items
     *
     * @return ContentItem[]
     */
    public function getContentItems ()
    {
        return $this->contentItems;
    }

    /**
     * A page is considered "dynamic" if it is dynamically generated from data in a collection.
     *
     * @return bool
     */
    public function isDynamicPage ()
    {
        return isset($this->frontMatter['collection']);
    }
}