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

    /**
     * Check whether or not a value will be handled by a __get() definition in the object implementing this interface.
     * If there are no __get() implementations, this function should just return FALSE.
     *
     * @param  mixed $value
     *
     * @return bool
     */
    public function isMagicGet ($value);
}