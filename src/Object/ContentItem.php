<?php

namespace allejo\stakx\Object;

use allejo\stakx\Engines\MarkdownEngine;
use allejo\stakx\Engines\RstEngine;
use allejo\stakx\Manager\TwigManager;

class ContentItem extends FrontMatterObject implements \JsonSerializable
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

    /**
     * {@inheritdoc}
     */
    public function createJail ()
    {
        return (new JailObject($this, array_merge(self::$whiteListFunctions, array(
            'getCollection'
        )), array('getPageView' => 'getJailedPageView')));
    }

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
            $twig = TwigManager::getInstance();

            if ($twig instanceof \Twig_Environment)
            {
                $template = $twig->createTemplate($this->bodyContent);
                $this->bodyContent = $template->render(array());
            }

            switch ($this->getExtension())
            {
                case "md":
                case "markdown":
                    $pd = new MarkdownEngine();
                    break;

                case "rst":
                    $pd = new RstEngine();
                    $pd->setIncludePolicy(true, getcwd());
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

    public function getJailedPageView ()
    {
        return $this->parentPageView->createJail();
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

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize()
    {
        return array_merge($this->getFrontMatter(), array(
            'content' => $this->getContent()
        ));
    }
}