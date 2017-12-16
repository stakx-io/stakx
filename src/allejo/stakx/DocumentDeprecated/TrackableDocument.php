<?php

/**
 * @copyright 2017 Vladimir Jimenez
 * @license   https://github.com/allejo/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\DocumentDeprecated;

/**
 * This interface defines the requirements for an object to be handled by a TrackingManager based class.
 */
interface TrackableDocument extends \ArrayAccess
{
    /**
     * The relative file path from the root of the website project to this object's corresponding file
     *
     * @return string
     */
    public function getRelativeFilePath();

    /**
     * A unique name to identify this object by
     *
     * @return string
     */
    public function getObjectName();
}
