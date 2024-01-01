<?php

/**
 * @copyright 2018 Vladimir Jimenez
 * @license   https://github.com/stakx-io/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\MarkupEngine;

use __;
use allejo\stakx\Document\ContentItem;
use allejo\stakx\Manager\AssetManager;
use allejo\stakx\Markup\AssetHandlerTrait;
use allejo\stakx\Markup\MarkdownParser;
use allejo\stakx\Markup\SyntaxHighlighterTrait;
use allejo\stakx\RuntimeStatus;
use allejo\stakx\Service;
use Highlight\Highlighter;

class MarkdownEngine implements MarkupEngineInterface
{
    private MarkdownParser $parser;

    public function __construct(AssetManager $assetManager)
    {
        $this->parser = new MarkdownParser($assetManager);
    }

    public function parse(string $content, ContentItem $contentItem = null): string
    {
        $this->parser->setContentItem($contentItem);

        return $this->parser->parse($content);
    }

    public function getTemplateTag(): ?string
    {
        return 'markdown';
    }

    public function getExtensions(): array
    {
        return [
            'md',
            'mdown',
            'markdown',
        ];
    }
}
