<?php declare(strict_types=1);

/**
 * @copyright 2018 Vladimir Jimenez
 * @license   https://github.com/stakx-io/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\MarkupEngine;

class PlainTextEngine implements MarkupEngineInterface
{
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
    public function parse($content, $contentItem = null): string
    {
        return $content;
    }
}
