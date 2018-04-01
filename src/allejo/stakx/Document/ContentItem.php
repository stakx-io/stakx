<?php

/**
 * @copyright 2017 Vladimir Jimenez
 * @license   https://github.com/allejo/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Document;

use allejo\stakx\Engines\Markdown\MarkdownEngine;
use allejo\stakx\Engines\PlainTextEngine;
use allejo\stakx\Engines\RST\RstEngine;
use allejo\stakx\Service;
use allejo\stakx\Templating\TemplateErrorInterface;

class ContentItem extends PermalinkFrontMatterDocument implements CollectableItem, TemplateReadyDocument
{
    use CollectableItemTrait;
    use TemplateEngineDependent;

    ///
    // Permalink management
    ///

    /**
     * {@inheritdoc}
     */
    public function handleSpecialRedirects()
    {
        $fm = $this->getFrontMatter();

        if (isset($fm['redirect_from']))
        {
            $redirects = $fm['redirect_from'];

            if (!is_array($redirects))
            {
                $redirects = [$redirects];
            }

            $this->redirects = array_merge($this->redirects, $redirects);
        }
    }

    ///
    // Document body transformation
    ///

    /**
     * @throws TemplateErrorInterface
     *
     * @return string
     */
    public function getContent()
    {
        if (!$this->bodyContentEvaluated)
        {
            $this->bodyContent = $this->parseTemplateLanguage($this->bodyContent);
            $this->parseMarkupLanguage();

            $this->bodyContentEvaluated = true;
        }

        return (string)$this->bodyContent;
    }

    /**
     * Transform the document's body from a markup language to HTML.
     *
     * @todo Port this to follow the same pattern as the template engine
     */
    private function parseMarkupLanguage()
    {
        switch ($this->getExtension())
        {
            case 'md':
            case 'markdown':
                $pd = new MarkdownEngine();
                break;

            case 'rst':
                $pd = new RstEngine();
                $pd->setIncludePolicy(true, Service::getWorkingDirectory());
                break;

            default:
                $pd = new PlainTextEngine();
                break;
        }

        $this->bodyContent = $pd->parse($this->bodyContent);
    }

    /**
     * {@inheritdoc}
     */
    public function createJail()
    {
        $whiteListedFunctions = array_merge(self::$whiteListedFunctions, [
        ]);

        $jailedFunctions = [
            'getPageView'   => 'getJailedPageView',
            'getCollection' => 'getNamespace',
        ];

        return (new JailedDocument($this, $whiteListedFunctions, $jailedFunctions));
    }

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize()
    {
        return array_merge($this->getFrontMatter(), [
            'content'   => $this->getContent(),
            'permalink' => $this->getPermalink(),
            'redirects' => $this->getRedirects(),
        ]);
    }
}
