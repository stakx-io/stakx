<?php

/**
 * @copyright 2018 Vladimir Jimenez
 * @license   https://github.com/allejo/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\MarkupEngine;

interface MarkupEngine
{
    const CONTAINER_TAG = 'stakx.markup_engine';

    /**
     * Get the custom tag and filter to register with our templating engine.
     *
     * For the Markdown engine, we'd be registering a tag called 'markdown' and a filter called 'markdown' to support
     * the following example.
     *
     * ```twig
     * {% markdown %}{% endmarkdown %}
     *
     * {{ 'my content' | markdown }}
     * ```
     *
     * @return null|string If null, then no tag or filter will be registered.
     */
    public function getTemplateTag();

    /**
     * List of extensions this engine will accept and parse.
     *
     * @return string[]
     */
    public function getExtensions();

    /**
     * Parse a given string using this engine.
     *
     * @param string $content
     *
     * @return string
     */
    public function parse($content);
}
