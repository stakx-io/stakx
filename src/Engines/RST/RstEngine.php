<?php

namespace allejo\stakx\Engines;

use allejo\stakx\Engines\RST\SyntaxBlock;
use Gregwar\RST\Parser;

class RstEngine extends Parser
{
    public function __construct ($environment = null, $kernel = null)
    {
        parent::__construct($environment, $kernel);

        $this->registerDirective(new SyntaxBlock());
    }
}