<?php

namespace allejo\stakx\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class WatchCommand extends BuildableCommand
{
    protected function configure ()
    {
        parent::configure();

        $this->setName('watch');
        $this->setDescription('Watch the source folder and rebuild the website as files change');
    }

    protected function execute (InputInterface $input, OutputInterface $output)
    {
        parent::execute($input, $output);

        $status = $this->configureBuild($input, $output);

        if ($status)
        {
            $output->writeln(sprintf("Watching %s...", getcwd()));
            $this->website->watch();
        }
    }
}