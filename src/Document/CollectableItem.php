<?php declare(strict_types=1);

/**
 * @copyright 2018 Vladimir Jimenez
 * @license   https://github.com/stakx-io/stakx/blob/master/LICENSE.md MIT
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
     */
    public function getNamespace(): ?string;

    public function setNamespace(?string $namespace): void;

    /**
     * Get the parent DynamicPageViews that this Collectable Item belongs to.
     *
     * @return DynamicPageView[]
     */
    public function &getParentPageViews(): array;

    /**
     * Save a reference to the parent DynamicPageView this Collectable Item belongs to.
     */
    public function saveParentPageView(DynamicPageView $pageView): void;

    /**
     * Evaluate the FrontMatter in this object by merging a custom array of data.
     *
     * @param array $variables        An array of YAML variables to use in evaluating the `$permalink` value
     * @param array $complexVariables complex variables that will be available to the FrontMatter scope of this document
     */
    public function evaluateFrontMatter(array $variables = [], array $complexVariables = []);
}
