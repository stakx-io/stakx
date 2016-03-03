<?php

namespace allejo\stakx\Object;

class ContentItem extends FrontMatterObject
{
    private $permalink;
    private $template;
    private $itemDate;

    public function __construct ($filePath)
    {
        parent::__construct($filePath);

        if (isset($this->frontMatter['date']))
        {
            $this->itemDate    = new \DateTime($this->frontMatter['date']);
            $this->frontMatter['year']  = $this->itemDate->format('Y');
            $this->frontMatter['month'] = $this->itemDate->format('m');
            $this->frontMatter['day']   = $this->itemDate->format('d');
        }
    }

    public function getContent ()
    {
        $pd = new \Parsedown();

        return $pd->parse($this->fileContent);
    }

    public function getPermalink ()
    {
        return $this->permalink;
    }

    public function getTemplate ()
    {
        return $this->template;
    }

    public function _setPermalink ($permalink)
    {
        $this->permalink = $permalink;
    }

    public function _setTemplate ($template)
    {
        $this->template = $template;
    }
}