<?php declare(strict_types=1);

/**
 * @copyright 2018 Vladimir Jimenez
 * @license   https://github.com/stakx-io/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Exception;

use allejo\stakx\Filesystem\File;
use Exception;
use RuntimeException;

class RecursiveConfigurationException extends RuntimeException
{
    public function __construct(private readonly File $import, $message = '', $code = 0, Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    public function getRecursiveImport()
    {
        return $this->import->getRelativeFilePath();
    }
}
