<?php

/**
 * @copyright 2017 Vladimir Jimenez
 * @license   https://github.com/allejo/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Document;

/**
 * Allows an object to be stored in a JailObject.
 *
 * @see JailedDocument
 */
interface JailedDocumentInterface
{
    /**
     * Create a JailObject instance from the object implementing this interface.
     *
     * @return JailedDocument
     */
    public function createJail();
}
