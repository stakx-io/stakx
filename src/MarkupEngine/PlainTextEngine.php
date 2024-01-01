<?php

/**
 * @copyright 2018 Vladimir Jimenez
 * @license   https://github.com/stakx-io/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\MarkupEngine;

use allejo\stakx\Document\ContentItem;

class PlainTextEngine implements MarkupEngineInterface
{
    public function getTemplateTag(): ?string
    {
        return null;
    }

    public function getExtensions(): array
    {
        return [
            'txt',
        ];
    }

    public function parse(string $content, ContentItem $contentItem = null): string
    {
        return $content;
    }
}
