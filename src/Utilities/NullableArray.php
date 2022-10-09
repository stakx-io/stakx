<?php declare(strict_types=1);

/**
 * @copyright 2018 Vladimir Jimenez
 * @license   https://github.com/stakx-io/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Utilities;

use ArrayAccess;

class NullableArray implements ArrayAccess
{
    public function __construct(private array $data = [])
    {
    }

    /**
     * {@inheritdoc}
     */
    public function offsetExists($offset): bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function offsetGet($offset): mixed
    {
        if (isset($this->data[$offset])) {
            return $this->data[$offset];
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function offsetSet($offset, $value): void
    {
        if ($offset === null) {
            return;
        }

        $this->data[$offset] = $value;
    }

    /**
     * {@inheritdoc}
     */
    public function offsetUnset($offset): void
    {
        if (isset($this->data[$offset])) {
            unset($this->data[$offset]);
        }
    }
}
