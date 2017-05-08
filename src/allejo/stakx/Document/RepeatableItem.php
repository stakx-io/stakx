<?php

/**
 * @copyright 2017 Vladimir Jimenez
 * @license   https://github.com/allejo/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Document;

/**
 * This interface defines what an object needs in order for a dynamic PageView to treat it as an item of a group of
 * files.
 */
interface RepeatableItem
{
    /**
     * @return string
     */
    public function getObjectName();

    /**
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
     * @param PageView $pageView
     *
     * @return void
     */
    public function setPageView(&$pageView);
}