<?php

/**
 * @copyright 2018 Vladimir Jimenez
 * @license   https://github.com/stakx-io/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Exception;

use Throwable;

class UnsupportedDataTypeException extends \RuntimeException
{
    public function __construct(private readonly string $dataType, string $message = '', int $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    public function getDataType(): string
    {
        return $this->dataType;
    }
}
