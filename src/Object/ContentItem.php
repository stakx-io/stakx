<?php

namespace allejo\stakx\Object;

use allejo\stakx\Engines\MarkdownEngine;
use allejo\stakx\Engines\RstEngine;

class ContentItem extends FrontMatterObject
{
    /**
     * The collection this Content Item belongs to
     *
     * @var string
     */
    private $parentCollection;

    /**
     * The Page View that will be used to render this Content Item
     *
     * @var PageView
     */
    private $parentPageView;

    public function getCollection ()
    {
        return $this->parentCollection;
    }

    public function setCollection ($collection)
    {
        $this->parentCollection = $collection;
    }

    /**
     * Return the body of the Content Item parsed as markdown
     *
     * @return string
     */
    public function getContent ()
    {
        if (!$this->bodyContentEvaluated)
        {
            $twig = Website::getTwigInstance();

            if ($twig instanceof \Twig_Environment)
            {
                $template = $twig->createTemplate($this->bodyContent);
                $this->bodyContent = $template->render(array());
            }

            switch ($this->extension)
            {
                case "md":
                case "markdown":
                    $pd = new MarkdownEngine();
                    break;

                case "rst":
                    $pd = new RstEngine();
                    break;

                default:
                    $pd = null;
                    break;
            }

            if (!is_null($pd)) // No parser needed
            {
                $this->bodyContent = $pd->parse($this->bodyContent);
            }

            $this->bodyContentEvaluated = true;
        }

        return (string)$this->bodyContent;
    }

    /**
     * @return PageView
     */
    public function &getPageView ()
    {
        return $this->parentPageView;
    }

    /**
     * Set the parent Page View that this Content Item will have be assigned to
     *
     * @param PageView $pageView
     */
    public function setPageView (&$pageView)
    {
        $this->parentPageView = &$pageView;
    }
}