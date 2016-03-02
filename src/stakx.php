<?php

namespace allejo\stakx;

use allejo\stakx\Core\Cli;
use allejo\stakx\Core\Configuration;

require("../vendor/autoload.php");

$cli =  new Cli();
$config = new Configuration("_config.yml");


\Logger::verbose($cli->getVerbose(), "Hello %s", "World");