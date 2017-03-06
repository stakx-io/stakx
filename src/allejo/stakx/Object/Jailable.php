<?php

/**
 * @copyright 2016 Vladimir Jimenez
 * @license   https://github.com/allejo/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Object;

/**
 * Allows an object to be stored in a JailObject
 *
 * @package allejo\stakx\Object
 * @see JailObject
 */
interface Jailable
{
    /**
     * Create a JailObject instance from the object implementing this interface
     *
     * @return JailObject
     */
    public function createJail ();
}