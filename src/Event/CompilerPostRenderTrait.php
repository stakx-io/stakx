<?php

/**
 * @copyright 2018 Vladimir Jimenez
 * @license   https://github.com/stakx-io/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Event;

use Stringable;

trait CompilerPostRenderTrait
{
    protected string|Stringable $compiledOutput;

    public function getCompiledOutput(): string
    {
        return (string)$this->compiledOutput;
    }

    /**
     * Modify the compiled output.
     */
    public function setCompiledOutput(string|Stringable $compiledOutput): void
    {
        $this->compiledOutput = $compiledOutput;
    }
}
