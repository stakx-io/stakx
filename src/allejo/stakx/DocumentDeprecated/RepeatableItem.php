<?php

/**
 * @copyright 2017 Vladimir Jimenez
 * @license   https://github.com/allejo/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\DocumentDeprecated;

/**
 * This interface defines what an object needs in order for a dynamic PageView to treat it as an item of a group of
 * files.
 */
interface RepeatableItem
{
    /**
     * A unique identifier for the object.
     *
     * The unique identifier is *typically* the file name.
     *
     * @return string
     */
    public function getObjectName();

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
     * Set the PageView that is tasked with generating the page for this item.
     *
     * @param PageView $pageView
     *
     * @return void
     */
    public function setParentPageView(PageView &$pageView);
}
