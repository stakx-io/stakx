<?php

namespace allejo\stakx\Core;

class Logger
{
    private $verboseEnabled;

    public function __construct($options)
    {
        $this->verboseEnabled = $options["verbose"];
    }

    public function verbose($message, $args)
    {
        if ($this->verboseEnabled)
        {
            echo call_user_func_array('sprintf', func_get_args()) . "\n";
        }
    }
}