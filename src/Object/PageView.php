<?php

namespace allejo\stakx\Object;

class PageView extends ContentItem
{
    /**
     * @return string Twig body
     */
    public function getContent ()
    {
        return $this->bodyContent;
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