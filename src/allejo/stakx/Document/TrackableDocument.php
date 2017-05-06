<?php

/**
 * @copyright 2017 Vladimir Jimenez
 * @license   https://github.com/allejo/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Document;

interface TrackableDocument
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
