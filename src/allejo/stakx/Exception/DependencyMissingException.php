<?php

/**
 * @copyright 2017 Vladimir Jimenez
 * @license   https://github.com/allejo/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Exception;

use Throwable;

class DependencyMissingException extends \RuntimeException
{
    private $dependency;

    public function __construct($dependency, $message = '', $code = 0, Throwable $previous = null)
    {
        $this->dependency = $dependency;

        parent::__construct($message, $code, $previous);
    }

    /**
     * @return string
     */
    public function getDependency()
    {
        return $this->dependency;
    }
}
