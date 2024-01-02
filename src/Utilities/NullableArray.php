<?php

/**
 * @copyright 2018 Vladimir Jimenez
 * @license   https://github.com/stakx-io/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Utilities;

class NullableArray implements \ArrayAccess
{
    private $data;

    public function __construct(array $arr = [])
    {
        $this->data = $arr;
    }

    public function offsetExists($offset): bool
    {
        return true;
    }

    public function offsetGet($offset): mixed
    {
        if (isset($this->data[$offset]))
        {
            return $this->data[$offset];
        }

        return null;
    }

    public function offsetSet($offset, $value): void
    {
        if ($offset === null)
        {
            return;
        }

        $this->data[$offset] = $value;
    }

    public function offsetUnset($offset): void
    {
        if (isset($this->data[$offset]))
        {
            unset($this->data[$offset]);
        }
    }
}
