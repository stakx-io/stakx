<?php declare(strict_types=1);

/**
 * @copyright 2018 Vladimir Jimenez
 * @license   https://github.com/stakx-io/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\MarkupEngine;

use allejo\stakx\Document\ContentItem;
use allejo\stakx\Manager\AssetManager;

/**
 * This interface defines the markup engines stakx will know how to parse as ContentItems and as regular text within
 * Twig by registering custom tags and filters.
 *
 * @since 0.2.0
 */
interface MarkupEngineInterface
{
    public const CONTAINER_TAG = 'stakx.markup_engine';

    public function __construct(AssetManager $assetManager);

    /**
     * Get the custom tag and filter to register with our templating engine.
     *
     * For the Markdown engine, we'd be registering a tag called 'markdown' and a filter called 'markdown' to support
     * the following example.
     *
     * ```twig
     * {% markdown %}my content{% endmarkdown %}
     *
     * {{ 'my content' | markdown }}
     * ```
     *
     * @since 0.2.0
     *
     * @return null|string if null, then no tag or filter will be registered
     */
    public function getTemplateTag(): ?string;

    /**
     * List of file extensions this engine will be charged with parsing.
     *
     * @since 0.2.0
     *
     * @return string[]
     */
    public function getExtensions(): array;

    /**
     * Parse a given string using this engine.
     *
     * @param string           $content
     * @param null|ContentItem $contentItem
     *
     * @since 0.2.0
     */
    public function parse(string $content, ?ContentItem $contentItem = null): string;
}