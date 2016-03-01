<?php

use Ulrichsg\Getopt\Getopt;
use Ulrichsg\Getopt\Option;

require("../vendor/autoload.php");

$getopt = new Getopt(
    new Option('v', 'verbose', Getopt::OPTIONAL_ARGUMENT)
);

$getopt->parse();