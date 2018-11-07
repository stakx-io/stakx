<?php

namespace allejo\stakx\Event;

use allejo\stakx\Utilities\StrUtils;

trait CompilerPostRenderTrait
{
    /** @var string */
    protected $compiledOutput;

    public function getCompiledOutput()
    {
        return (string)$this->compiledOutput;
    }

    /**
     * Modify the compiled output.
     *
     * @param string $compiledOutput
     */
    public function setCompiledOutput($compiledOutput)
    {
        if (!StrUtils::canBeCastedToString($compiledOutput))
        {
            @trigger_error('CompileProcessPostRenderPageView :: Value cannot be set to something that cannot be cast into a string.', E_USER_WARNING);

            return;
        }

        $this->compiledOutput = $compiledOutput;
    }
}
