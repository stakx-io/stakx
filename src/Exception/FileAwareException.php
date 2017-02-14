<?php

/**
 * @copyright 2017 Vladimir Jimenez
 * @license   https://github.com/allejo/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Exception;

/**
 * Exception thrown when an error is found in a file
 *
 * @package allejo\stakx\Exception
 */
class FileAwareException extends \RuntimeException
{
    private $lineNumber;
    private $filePath;

    public function __construct($message = "", $code = 0, \Exception $previous = null, $path = "", $line = -1)
    {
        parent::__construct($message, $code, $previous);

        $this->filePath = $path;
        $this->lineNumber = $line;
    }

    public function getLineNumber ()
    {
        return $this->lineNumber;
    }

    public function getPath ()
    {
        return $this->filePath;
    }
}