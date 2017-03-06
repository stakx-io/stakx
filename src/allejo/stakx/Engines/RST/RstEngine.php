<?php

namespace allejo\stakx\Engines\RST;

use allejo\stakx\Engines\ParsingEngine;
use Gregwar\RST\Parser;

class RstEngine extends Parser implements ParsingEngine
{
    public function __construct ($environment = null, $kernel = null)
    {
        parent::__construct($environment, $kernel);

        $this->registerDirective(new SyntaxBlock());
    }
}