<?php

/**
 * @copyright 2018 Vladimir Jimenez
 * @license   https://github.com/stakx-io/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Document;

use allejo\stakx\MarkupEngine\MarkupEngineInterface;
use allejo\stakx\MarkupEngine\MarkupEngineManager;
use allejo\stakx\Templating\TemplateErrorInterface;

class ContentItem extends PermalinkFrontMatterDocument implements CollectableItem, TemplateReadyDocument
{
    use CollectableItemTrait;
    use TemplateEngineDependent;

    private MarkupEngineInterface $markupEngine;

    public function setMarkupEngine(MarkupEngineManager $manager): void
    {
        $this->markupEngine = $manager->getEngineByExtension($this->getExtension());
    }

    ///
    // Permalink management
    ///

    public function handleSpecialRedirects(): void
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
     */
    public function getContent(): string
    {
        if (!$this->bodyContentEvaluated)
        {
            $this->bodyContent = $this->parseTemplateLanguage($this->bodyContent);

            if ($this->markupEngine)
            {
                $this->bodyContent = $this->markupEngine->parse($this->bodyContent, $this);
            }

            $this->bodyContentEvaluated = true;
        }

        return (string)$this->bodyContent;
    }

    /**
     * {@inheritdoc}
     */
    public function createJail(): JailedDocument
    {
        $whiteListedFunctions = array_merge(self::$whiteListedFunctions, [
        ]);

        $jailedFunctions = [
            'getCollection' => 'getNamespace',
        ];

        return new JailedDocument($this, $whiteListedFunctions, $jailedFunctions);
    }

    public function jsonSerialize(): mixed
    {
        return array_merge($this->getFrontMatter(), [
            'content' => $this->getContent(),
            'permalink' => $this->getPermalink(),
            'redirects' => $this->getRedirects(),
        ]);
    }
}
