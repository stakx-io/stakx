<?php

/**
 * @copyright 2017 Vladimir Jimenez
 * @license   https://github.com/allejo/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Document;

/**
 * An interface for objects that can be stored in a group, e.g. a Collection or a Dataset.
 */
interface CollectableItem
{
    public function getRelativeFilePath();

    /**
     * Get the namespace this item belongs.
     *
     * - In a Collection, the name of the Collection is the namespace.
     * - In a DataSet, the name of the DataSet is the namespace.
     *
     * @return string
     */
    public function getNamespace();

    /**
     * @param string $namespace
     *
     * @return void
     */
    public function setNamespace($namespace);

    /**
     * Get the PageView that renders this document.
     *
     * @return DynamicPageView
     */
    public function &getParentPageView();

    /**
     * Get a jailed version of the parent PageView.
     *
     * @return JailedDocument
     */
    public function getJailedPageView();

    /**
     * Set the PageView that is tasked with generating the page for this item.
     *
     * @param DynamicPageView $pageView
     *
     * @return void
     */
    public function setParentPageView(DynamicPageView &$pageView);

    /**
     * Evaluate the FrontMatter in this object by merging a custom array of data.
     *
     * @param array|null $variables An array of YAML variables to use in evaluating the `$permalink` value
     */
    public function evaluateFrontMatter(array $variables = null);
}
