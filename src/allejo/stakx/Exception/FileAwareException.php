<?php

/**
 * @copyright 2018 Vladimir Jimenez
 * @license   https://github.com/stakx-io/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Exception;

/**
 * Exception thrown when an error is found in a file.
 */
class FileAwareException extends \RuntimeException
{
    private $lineNumber;
    private $filePath;

    public function __construct($message = '', $code = 0, \Exception $previous = null, $path = '', $line = -1)
    {
        parent::__construct($message, $code, $previous);

        $this->filePath = $path;
        $this->lineNumber = $line;
    }

    public function getLineNumber()
    {
        return $this->lineNumber;
    }

    public function getPath()
    {
        return $this->filePath;
    }

    public static function castException(\Exception $e, $filePath)
    {
        $lineNumber = ($e instanceof \Twig_Error_Syntax) ? $e->getTemplateLine() : -1;

        $exception = new self(
            $e->getMessage(),
            $e->getCode(),
            $e,
            $filePath,
            $lineNumber
        );

        return $exception;
    }
}
