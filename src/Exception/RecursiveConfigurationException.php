<?php

/**
 * @copyright 2018 Vladimir Jimenez
 * @license   https://github.com/stakx-io/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Exception;

use allejo\stakx\Filesystem\File;
use Exception;

class RecursiveConfigurationException extends \RuntimeException
{
    public function __construct(private readonly File $import, string $message = '', int $code = 0, Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    public function getRecursiveImport(): string
    {
        return $this->import->getRelativeFilePath();
    }
}
