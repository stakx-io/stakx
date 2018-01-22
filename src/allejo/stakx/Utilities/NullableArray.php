<?php

/**
 * @copyright 2018 Vladimir Jimenez
 * @license   https://github.com/allejo/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Utilities;

class NullableArray implements \ArrayAccess
{
    private $data;

    public function __construct(array $arr = [])
    {
        $this->data = $arr;
    }

    /**
     * {@inheritdoc}
     */
    public function offsetExists($offset)
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function offsetGet($offset)
    {
        if (isset($this->data[$offset]))
        {
            return $this->data[$offset];
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function offsetSet($offset, $value)
    {
        if ($offset === null) {
            return;
        }

        $this->data[$offset] = $value;
    }

    /**
     * {@inheritdoc}
     */
    public function offsetUnset($offset)
    {
        if (isset($this->data[$offset]))
        {
            unset($this->data[$offset]);
        }
    }
}
