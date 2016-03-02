<?php

namespace allejo\stakx;

require("../vendor/autoload.php");

use allejo\stakx\Core\Cli;
use allejo\stakx\Core\Configuration;
use allejo\stakx\Core\Logger;
use allejo\stakx\FileSystem\FileSystem;

$fs     = new Filesystem();
$cli    = new Cli();
$config = new Configuration();
$logger = new Logger(array(
    "verbose" => $cli->getVerbose()
));

if ($cli->getAction() == Cli::BUILD)
{
    $fileSystem = $fs->ls();
}
