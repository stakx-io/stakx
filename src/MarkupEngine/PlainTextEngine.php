<?php declare(strict_types=1);

/**
 * @copyright 2018 Vladimir Jimenez
 * @license   https://github.com/stakx-io/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\MarkupEngine;

use allejo\stakx\Document\ContentItem;
use allejo\stakx\Manager\AssetManager;

class PlainTextEngine implements MarkupEngineInterface
{
    public function __construct(AssetManager $assetManager)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function getTemplateTag(): ?string
    {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function getExtensions(): array
    {
        return [
            'txt',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function parse(string $content, ?ContentItem $contentItem = null): string
    {
        return $content;
    }
}
