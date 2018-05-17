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
    private $import;

    public function __construct(File $import, $message = '', $code = 0, Exception $previous = null)
    {
        $this->import = $import;

        parent::__construct($message, $code, $previous);
    }

    public function getRecursiveImport()
    {
        return $this->import->getRelativeFilePath();
    }
}
