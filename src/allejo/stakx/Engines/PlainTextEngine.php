<?php

/**
 * @copyright 2017 Vladimir Jimenez
 * @license   https://github.com/allejo/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Engines;

class PlainTextEngine implements ParsingEngine
{
    /**
     * @param string $context
     */
    public function parse($context)
    {
        return $context;
    }
}
