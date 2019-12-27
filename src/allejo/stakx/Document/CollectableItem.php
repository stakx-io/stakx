<?php

/**
 * @copyright 2018 Vladimir Jimenez
 * @license   https://github.com/stakx-io/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Document;

use allejo\stakx\Filesystem\File;

/**
 * An interface for objects that can be stored in a group, e.g. a Collection or a Dataset.
 */
interface CollectableItem
{
    /**
     * Attach an asset to this ContentItem.
     *
     * @param File $asset
     */
    public function attachAsset(File $asset);

    /**
     * Get the assets attached to this CollectableItem.
     *
     * @return array<int, File>
     */
    public function &getAssets();

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
     * Get the parent DynamicPageViews that this Collectable Item belongs to.
     *
     * @return DynamicPageView[]
     */
    public function &getParentPageViews();

    /**
     * Save a reference to the parent DynamicPageView this Collectable Item belongs to.
     *
     * @param DynamicPageView $pageView
     *
     * @return void
     */
    public function saveParentPageView(DynamicPageView &$pageView);

    /**
     * Evaluate the FrontMatter in this object by merging a custom array of data.
     *
     * @param array $variables        An array of YAML variables to use in evaluating the `$permalink` value
     * @param array $complexVariables complex variables that will be available to the FrontMatter scope of this document
     */
    public function evaluateFrontMatter(array $variables = [], array $complexVariables = []);
}
