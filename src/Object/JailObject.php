<?php

/**
 * @copyright 2016 Vladimir Jimenez
 * @license   https://github.com/allejo/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Object;

/**
 * Class JailObject
 *
 * A wrapper object to only allow certain functions on the white list to be called. This is used in order to limit which
 * functions a user can call from Twig templates to prevent unexpected behavior.
 *
 * @package allejo\stakx\Object
 */
class JailObject implements \ArrayAccess
{
    /**
     * @var string[]
     */
    private $whiteListFunctions;

    /**
     * @var string[]
     */
    private $jailedFunctions;

    /**
     * @var Jailable
     */
    private $object;

    /**
     * JailObject constructor.
     *
     * @param Jailable $object             The object that will be jailed
     * @param array    $whiteListFunctions A list of function names that can be called
     * @param array    $jailedFunctions
     */
    public function __construct (&$object, array $whiteListFunctions, array $jailedFunctions = array())
    {
        if (!($object instanceof Jailable) && !($object instanceof \ArrayAccess))
        {
            throw new \InvalidArgumentException('Must implement the ArrayAccess and Jailable interfaces');
        }

        $this->object = &$object;
        $this->whiteListFunctions = $whiteListFunctions;
        $this->jailedFunctions = $jailedFunctions;
    }

    public function __call ($name, $arguments)
    {
        // White listed functions will always be getter functions, so somehow get the name of a possible getter function
        // name.
        $lcName = lcfirst($name);
        $getFxnCall = ($lcName[0] === 'g' && strpos($lcName, 'get') === 0) ? $lcName : sprintf('get%s', ucfirst($name));

        // Check if our function call is a jailed call, meaning the function should be mapped to special "jailed"
        // jailed version of the function call.
        if (in_array($getFxnCall, $this->jailedFunctions))
        {
            return call_user_func_array(array($this->object, $this->jailedFunctions[$getFxnCall]), $arguments);
        }

        // Otherwise, test to see if the function call is in our white list and call it
        if (in_array($getFxnCall, $this->whiteListFunctions))
        {
            return call_user_func_array(array($this->object, $getFxnCall), $arguments);
        }

        // Functions should take precedence over __get(), so if it's not a function we'll search to see if the __get()
        // handles it
        if ($this->object->isMagicGet($name))
        {
            return $this->object->$name;
        }

        throw new \BadMethodCallException();
    }

    public function __get ($name)
    {
        if ($this->object->isMagicGet($name))
        {
            return $this->object->$name;
        }

        return NULL;
    }

    public function coreInstanceOf ($class)
    {
        return is_subclass_of($this->object, $class);
    }

    //
    // ArrayAccess Implementation
    //

    /**
     * {@inheritdoc}
     */
    public function offsetExists($offset)
    {
        return $this->object->offsetExists($offset);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetGet($offset)
    {
        return $this->object->offsetGet($offset);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetSet($offset, $value)
    {
        return $this->object->offsetSet($offset, $value);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetUnset($offset)
    {
        return $this->object->offsetUnset($offset);
    }
}