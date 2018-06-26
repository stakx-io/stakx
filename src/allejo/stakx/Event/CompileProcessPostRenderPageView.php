<?php

/**
 * @copyright 2018 Vladimir Jimenez
 * @license   https://github.com/stakx-io/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Event;

use allejo\stakx\Utilities\StrUtils;
use Symfony\Component\EventDispatcher\Event;

/**
 * This event is triggered after a PageView is compiled and before it is written to a file. The compiled output is
 * accessible and can be modified before it is written out to a file.
 *
 * @since 0.2.0
 */
class CompileProcessPostRenderPageView extends Event
{
    const NAME = 'compile.process.postrender_pageview';

    private $compiledOutput;
    private $pageViewType;

    /**
     * CompileProcessPostRenderPageView constructor.
     *
     * @param string $pageViewType
     * @param string $compiledOutput
     */
    public function __construct($pageViewType, $compiledOutput)
    {
        $this->compiledOutput = $compiledOutput;
        $this->pageViewType = $pageViewType;
    }

    /**
     * @return string
     */
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
        if (StrUtils::canBeCastedToString($compiledOutput))
        {
            @trigger_error('CompileProcessPostRenderPageView :: Value cannot be set to something that cannot be cast into a string.', E_USER_WARNING);

            return;
        }

        $this->compiledOutput = $compiledOutput;
    }
}
