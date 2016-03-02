<?php

namespace allejo\stakx\Core;

use Ulrichsg\Getopt\Getopt;
use Ulrichsg\Getopt\Option;

class Cli
{
    private $options;

    public function __construct()
    {
        $this->options = new Getopt(array(
            (new Option('v', 'verbose', Getopt::NO_ARGUMENT))
                ->setDescription('Enable verbose output about what is being done'),
            (new Option(null, 'version', Getopt::NO_ARGUMENT))
                ->setDescription('Show the current version')
        ));

        $this->options->parse();
    }

    public function getVerbose()
    {
        return $this->options->getOption('v');
    }
}