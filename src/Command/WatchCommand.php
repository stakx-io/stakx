<?php

namespace allejo\stakx\Command;

use Illuminate\Filesystem\Filesystem;
use JasonLewis\ResourceWatcher\Tracker;
use JasonLewis\ResourceWatcher\Watcher;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\OutputInterface;

class WatchCommand extends Command
{
    private $fs;
    private $trackers;

    protected function configure ()
    {
        $this->fs       = new Filesystem();
        $this->trackers = new Tracker();

        $this->setName('watch');
        $this->setDescription('Watch the source folder and rebuild the website as files change');
        $this->addOption('conf', 'c', InputOption::VALUE_REQUIRED, 'The configuration file to be used');
    }

    protected function execute (InputInterface $input, OutputInterface $output)
    {
        /** @var $buildCmd BuildCommand */
        $buildCmd = $this->getApplication()->find('build');
        $buildOpt = new ArrayInput(array());
        $buildCmd->run($buildOpt, $output);
        $website = $buildCmd->_getWebsite();

        $watcher = new Watcher($this->trackers, $this->fs);
        $listener = $watcher->watch('.');

        $listener->onModify(function($resource, $path) use ($website, $output) {
            $filePath =  str_replace(getcwd(), '', $path);

            if ($filePath[0] !== "_")
            {
                $output->writeln(sprintf("File changed: %s", $filePath));
                $website->handleSingleFile($filePath);
            }
        });

        $watcher->start();
    }
}