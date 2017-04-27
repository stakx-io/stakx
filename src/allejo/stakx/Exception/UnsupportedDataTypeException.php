<?php

/**
 * @copyright 2017 Vladimir Jimenez
 * @license   https://github.com/allejo/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Exception;

use Throwable;

class UnsupportedDataTypeException extends \RuntimeException
{
    private $dataType;

    public function __construct($dataType, $message = '', $code = 0, Throwable $previous = null)
    {
        $this->dataType = $dataType;

        parent::__construct($message, $code, $previous);
    }

    /**
     * @return string
     */
    public function getDataType()
    {
        return $this->dataType;
    }
}