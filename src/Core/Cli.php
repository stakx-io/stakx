<?php

namespace allejo\stakx\Core;

use Ulrichsg\Getopt\Getopt;
use Ulrichsg\Getopt\Option;

class Cli
{
    const UNDEF = 0;
    const BUILD = 1;

    private $options;

    public function __construct()
    {
        $this->options = new Getopt(array(
            (new Option(null, 'build', Getopt::NO_ARGUMENT))
                ->setDescription('Build the '),
            (new Option('v', 'verbose', Getopt::NO_ARGUMENT))
                ->setDescription('Enable verbose output about what is being done'),
            (new Option(null, 'version', Getopt::NO_ARGUMENT))
                ->setDescription('Show the current version')
        ));

        $this->options->parse();
    }

    public function getAction()
    {
        if ($this->options->getOption('build'))
        {
            return Cli::BUILD;
        }

        return Cli::UNDEF;
    }

    public function getVerbose()
    {
        return $this->options->getOption('v');
    }
}