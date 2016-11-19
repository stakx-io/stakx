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
     * @var PageView[]
     */
    private $children;

    /**
     * {@inheritdoc}
     */
    public function __construct($filePath)
    {
        parent::__construct($filePath);

        $this->children = array();
    }

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
     * Get child PageViews
     *
     * A child is defined as a static PageView whose URL has a parent. For example, a PageView with a URL of
     * `/gallery/france/` would have the PageView whose URL is `/gallery` as a parent.
     *
     * @return PageView[]
     */
    public function &getChildren ()
    {
        return $this->children;
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

    /**
     * A fallback for the site menus that use the `url` field.
     *
     * @deprecated 0.1.0
     * @todo Remove this in the next major release
     */
    public function getUrl ()
    {
        return $this->getPermalink();
    }
}