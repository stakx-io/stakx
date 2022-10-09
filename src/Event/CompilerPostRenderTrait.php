<?php declare(strict_types=1);

/**
 * @copyright 2018 Vladimir Jimenez
 * @license   https://github.com/stakx-io/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Event;

use allejo\stakx\Utilities\StrUtils;

trait CompilerPostRenderTrait
{
    protected string $compiledOutput;

    public function getCompiledOutput()
    {
        return (string)$this->compiledOutput;
    }

    /**
     * Modify the compiled output.
     *
     * @param string $compiledOutput
     */
    public function setCompiledOutput($compiledOutput): void
    {
        if (!StrUtils::canBeCastedToString($compiledOutput)) {
            @trigger_error('CompileProcessPostRenderPageView :: Value cannot be set to something that cannot be cast into a string.', E_USER_WARNING);

            return;
        }

        $this->compiledOutput = $compiledOutput;
    }
}
