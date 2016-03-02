<?php

require(__DIR__ . "/../vendor/autoload.php");

use allejo\stakx\Command\BuildCommand;
use Symfony\Component\Console\Application;

$application = new Application('stakx', '0.1.0');
$application->add(new BuildCommand());
$application->run();
