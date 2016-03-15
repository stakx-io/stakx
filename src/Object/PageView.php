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

    public function getTargetFile ()
    {
        $extension  = pathinfo($this->getPermalink(), PATHINFO_EXTENSION);
        $targetFile = $this->getPermalink();

        if ($extension === "")
        {
            $targetFile = rtrim($this->getPermalink(), '/') . '/';
        }

        return $targetFile;
    }

    /**
     * A page is considered "dynamic" if it is dynamically generated from data in a collection.
     *
     * @return bool
     */
    public function isDynamicPage ()
    {
        return isset($this->frontMatter['collections']);
    }
}