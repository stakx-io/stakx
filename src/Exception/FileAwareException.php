<?php

/**
 * @copyright 2018 Vladimir Jimenez
 * @license   https://github.com/stakx-io/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Exception;

use Twig\Error\SyntaxError;

/**
 * Exception thrown when an error is found in a file.
 */
class FileAwareException extends \RuntimeException
{
    public function __construct(
        string $message = '',
        int $code = 0,
        \Exception $previous = null,
        private readonly string $filePath = '',
        private readonly int $lineNumber = -1
    ) {
        parent::__construct($message, $code, $previous);
    }

    public function getLineNumber(): int
    {
        return $this->lineNumber;
    }

    public function getPath(): string
    {
        return $this->filePath;
    }

    public static function castException(\Exception $e, $filePath): FileAwareException
    {
        $lineNumber = ($e instanceof SyntaxError) ? $e->getTemplateLine() : -1;

        return new self(
            $e->getMessage(),
            $e->getCode(),
            $e,
            $filePath,
            $lineNumber
        );
    }
}
