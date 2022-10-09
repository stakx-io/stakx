<?php

/**
 * @copyright 2018 Vladimir Jimenez
 * @license   https://github.com/stakx-io/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Document;

/**
 * A wrapper object to only allow certain functions on the white list to be called and will redirect "jailed" function
 * calls to their appropriate jailed calls. This is used in order to limit which functions a user can call from
 * templates to prevent unexpected behavior.
 */
class JailedDocument implements \ArrayAccess, \IteratorAggregate, \JsonSerializable
{
    /** @var string[] */
    private $whiteListFunctions;

    /** @var string[] */
    private $jailedFunctions;

    /** @var TemplateReadyDocument */
    private $object;

    /** @var array */
    private $debugInfo;

    /**
     * JailObject constructor.
     *
     * @param TemplateReadyDocument $object             the object that will be jailed
     * @param array                 $whiteListFunctions a list of function names that can be called
     * @param array                 $jailedFunctions    a list of functions that will be redirected to another function
     */
    public function __construct(TemplateReadyDocument &$object, array $whiteListFunctions, array $jailedFunctions = [])
    {
        $this->object = &$object;
        $this->whiteListFunctions = $whiteListFunctions;
        $this->jailedFunctions = $jailedFunctions;
        $this->debugInfo = [];
    }

    public function __call($name, $arguments)
    {
        // White listed functions will always be getter functions, so somehow get the name of a possible getter function
        // name.
        $lcName = lcfirst($name);
        $getFxnCall = ($lcName[0] === '_' || strpos($lcName, 'get') === 0) ? $lcName : sprintf('get%s', ucfirst($name));

        // Check if our function call is a jailed call, meaning the function should be mapped to special "jailed"
        // jailed version of the function call.
        if (array_key_exists($getFxnCall, $this->jailedFunctions))
        {
            return call_user_func_array([$this->object, $this->jailedFunctions[$getFxnCall]], $arguments);
        }

        // Otherwise, test to see if the function call is in our white list and call it
        if (in_array($getFxnCall, $this->whiteListFunctions))
        {
            return call_user_func_array([$this->object, $getFxnCall], $arguments);
        }

        throw new \BadMethodCallException();
    }

    public function __debugInfo()
    {
        if (!empty($this->debugInfo))
        {
            return $this->debugInfo;
        }

        if ($this->object instanceof FrontMatterDocument)
        {
            $this->debugInfo = $this->object->getFrontMatter(true);
        }

        foreach ($this->whiteListFunctions as $function)
        {
            $value = preg_replace('/^(get|is)/', '', $function);
            $value = lcfirst($value);

            try
            {
                $this->debugInfo[$value] = call_user_func([$this, $function]);
            }
            catch (\BadMethodCallException $e)
            {
                // Just throw away this information because there's no point in listing an accessible value in this
                // object that doesn't actually exist.
            }
        }

        return $this->debugInfo;
    }

    public function __toString()
    {
        @trigger_error("You're casting a JailedDocument into a string, did you really mean to do this?", E_USER_WARNING);

        return serialize($this->__debugInfo());
    }

    /**
     * Check if the jailed object is an instance of a given class.
     *
     * @param string $class
     *
     * @return bool
     */
    public function _coreInstanceOf($class)
    {
        return ($this->object instanceof $class) || is_subclass_of($this->object, $class);
    }

    ///
    // ArrayAccess Implementation
    ///

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
        throw new \LogicException('A jailed document is read-only.');
    }

    /**
     * {@inheritdoc}
     */
    public function offsetUnset($offset)
    {
        throw new \LogicException('A jailed document is read-only.');
    }

    ///
    // IteratorAggregate implementation
    ///

    /**
     * {@inheritdoc}
     */
    public function getIterator()
    {
        return $this->object->getIterator();
    }

    ///
    // JsonSerializable implementation
    ///

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize()
    {
        return $this->object->jsonSerialize();
    }
}
