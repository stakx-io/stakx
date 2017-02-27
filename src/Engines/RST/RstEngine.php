<?php

namespace allejo\stakx\Engines;

use allejo\stakx\Engines\RST\SyntaxBlock;
use Gregwar\RST\Parser;

class RstEngine extends Parser implements ParsingEngine
{
    public function __construct ($environment = null, $kernel = null)
    {
        parent::__construct($environment, $kernel);

        $this->registerDirective(new SyntaxBlock());
    }
}