<?php declare(strict_types=1);

/**
 * @copyright 2018 Vladimir Jimenez
 * @license   https://github.com/stakx-io/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Exception;

use RuntimeException;
use Throwable;

class DependencyMissingException extends RuntimeException
{
    public function __construct(private $dependency, $message = '', $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    public function getDependency(): string
    {
        return $this->dependency;
    }
}
