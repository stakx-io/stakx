<?php

/**
 * @copyright 2017 Vladimir Jimenez
 * @license   https://github.com/allejo/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Exception;

use Exception;

class RecursiveConfigurationException extends \RuntimeException
{
    private $import;

    public function __construct($import, $message = "", $code = 0, Exception $previous = null)
    {
        $this->import = $import;

        parent::__construct($message, $code, $previous);
    }

    public function getRecursiveImport()
    {
        return $this->import;
    }
}
