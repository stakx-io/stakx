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
}