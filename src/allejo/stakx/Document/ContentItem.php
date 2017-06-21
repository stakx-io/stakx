<?php

/**
 * @copyright 2017 Vladimir Jimenez
 * @license   https://github.com/allejo/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Document;

use allejo\stakx\Engines\Markdown\MarkdownEngine;
use allejo\stakx\Engines\PlainTextEngine;
use allejo\stakx\Engines\RST\RstEngine;
use allejo\stakx\FrontMatter\FrontMatterDocument;
use allejo\stakx\Manager\TwigManager;

class ContentItem extends FrontMatterDocument implements \JsonSerializable, RepeatableItem
{
    /**
     * The collection this Content Item belongs to.
     *
     * @var string
     */
    private $parentCollection;

    /**
     * The Page View that will be used to render this Content Item.
     *
     * @var PageView
     */
    private $parentPageView;

    /**
     * {@inheritdoc}
     */
    public function createJail()
    {
        return new JailedDocument($this, array_merge(self::$whiteListFunctions, array(
            'getCollection', 'isDraft'
        )), array('getPageView' => 'getJailedPageView'));
    }

    public function getNamespace()
    {
        return $this->parentCollection;
    }

    public function setNamespace($collection)
    {
        $this->parentCollection = $collection;
    }

    /**
     * Return the body of the Content Item parsed as markdown.
     *
     * @return string
     */
    public function getContent()
    {
        if (!$this->bodyContentEvaluated)
        {
            $this->parseTwig();
            $this->parseEngines();

            $this->bodyContentEvaluated = true;
        }

        return (string)$this->bodyContent;
    }

    /**
     * Parse the Twig that is embedded inside a ContentItem's body.
     */
    private function parseTwig()
    {
        $twig = TwigManager::getInstance();

        if ($twig instanceof \Twig_Environment)
        {
            $template = $twig->createTemplate($this->bodyContent);
            $this->bodyContent = $template->render(array());
        }
    }

    /**
     * Parse the ContentItem's body based on the extension of the file.
     */
    private function parseEngines()
    {
        switch ($this->getExtension())
        {
            case 'md':
            case 'markdown':
                $pd = new MarkdownEngine();
                break;

            case 'rst':
                $pd = new RstEngine();
                $pd->setIncludePolicy(true, getcwd());
                break;

            default:
                $pd = new PlainTextEngine();
                break;
        }

        $this->bodyContent = $pd->parse($this->bodyContent);
    }

    /**
     * @return PageView
     */
    public function &getPageView()
    {
        return $this->parentPageView;
    }

    public function getJailedPageView()
    {
        return $this->parentPageView->createJail();
    }

    /**
     * Set the parent Page View that this Content Item will have be assigned to.
     *
     * @param PageView $pageView
     */
    public function setParentPageView(PageView &$pageView)
    {
        $this->parentPageView = &$pageView;
    }

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize()
    {
        return array_merge($this->getFrontMatter(), array(
            'content'   => $this->getContent(),
            'permalink' => $this->getPermalink(),
            'redirects' => $this->getRedirects(),
        ));
    }
}
