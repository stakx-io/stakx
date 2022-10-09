<?php declare(strict_types=1);

/**
 * @copyright 2018 Vladimir Jimenez
 * @license   https://github.com/stakx-io/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Exception;

use Exception;
use RuntimeException;
use Twig_Error_Syntax;

/**
 * Exception thrown when an error is found in a file.
 */
class FileAwareException extends RuntimeException
{
    public function __construct($message = '', $code = 0, Exception $previous = null, private $filePath = '', private $lineNumber = -1)
    {
        parent::__construct($message, $code, $previous);
    }

    public function getLineNumber()
    {
        return $this->lineNumber;
    }

    public function getPath()
    {
        return $this->filePath;
    }

    public static function castException(Exception $e, $filePath)
    {
        $lineNumber = ($e instanceof Twig_Error_Syntax) ? $e->getTemplateLine() : -1;

        return new self(
            $e->getMessage(),
            $e->getCode(),
            $e,
            $filePath,
            $lineNumber
        );
    }
}
