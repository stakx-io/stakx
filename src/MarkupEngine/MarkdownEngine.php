<?php declare(strict_types=1);

/**
 * @copyright 2018 Vladimir Jimenez
 * @license   https://github.com/stakx-io/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\MarkupEngine;

use allejo\stakx\Document\ContentItem;
use allejo\stakx\Manager\AssetManager;
use allejo\stakx\Markup\AssetHandlerTrait;
use allejo\stakx\Markup\StakxDown;
use allejo\stakx\Markup\SyntaxHighlighterTrait;

class MarkdownEngine implements MarkupEngineInterface
{
    use AssetHandlerTrait;
    use SyntaxHighlighterTrait;

    private readonly StakxDown $mdParser;

    public function __construct(AssetManager $assetManager)
    {
        $this->mdParser = new StakxDown($assetManager);
    }

    public function parse(string $content, ?ContentItem $contentItem = null): string
    {
        $this->mdParser->setContentItem($contentItem);

        return $this->mdParser->parse($content);
    }

    //
    // MarkupEngine Implementation
    //

    /**
     * {@inheritdoc}
     */
    public function getTemplateTag(): string
    {
        return 'markdown';
    }

    /**
     * {@inheritdoc}
     */
    public function getExtensions(): array
    {
        return [
            'md',
            'mdown',
            'markdown',
        ];
    }
}
