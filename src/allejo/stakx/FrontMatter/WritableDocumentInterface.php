<?php

/**
 * @copyright 2017 Vladimir Jimenez
 * @license   https://github.com/allejo/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\FrontMatter;

interface WritableDocumentInterface
{
    /**
     * Evaluate the FrontMatter in this object by merging a custom array of data.
     *
     * @param array|null $variables An array of YAML variables to use in evaluating the `$permalink` value
     * @return void
     */
    public function evaluateFrontMatter($variables = null);

    /**
     * Get the Front Matter of a ContentItem as an array.
     *
     * @param bool $evaluateYaml When set to true, the YAML will be evaluated for variables
     *
     * @return array
     */
    public function getFrontMatter($evaluateYaml = true);

    /**
     * Returns true when the evaluated Front Matter has expanded values embeded.
     *
     * @return bool
     */
    public function hasExpandedFrontMatter();

    /**
     * Append a custom FrontMatter value.
     *
     * @param array $frontMatter
     * @return void
     */
    public function appendFrontMatter(array $frontMatter);

    /**
     * Delete a custom FrontMatter value.
     *
     * This will not delete a FrontMatter value parsed from the file
     *
     * @param string $key
     * @return void
     */
    public function deleteFrontMatter($key);

    /**
     * Set custom FrontMatter values.
     *
     * These custom values are temporary and will take precedence over Front Matter evaluated from the file but is only
     * available to Twig templates
     *
     * @param array $frontMatter
     * @return void
     */
    public function setFrontMatter(array $frontMatter);
}
