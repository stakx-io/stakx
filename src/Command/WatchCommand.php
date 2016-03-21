<?php

namespace allejo\stakx\Command;

use Illuminate\Filesystem\Filesystem;
use JasonLewis\ResourceWatcher\Tracker;
use JasonLewis\ResourceWatcher\Watcher;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class WatchCommand extends Command
{
    private $files;
    private $trackers;

    protected function configure ()
    {
        $this->files = new Filesystem();
        $this->trackers = new Tracker();

        $this->setName('watch');
        $this->setDescription('Watch the source folder and rebuild the website as files change');
    }

    protected function execute (InputInterface $input, OutputInterface $output)
    {
        $watcher = new Watcher($this->trackers, $this->files);
        $listener = $watcher->watch('.');

        $listener->onModify(function($resource, $path) {
            echo "{$path}\n";
        });

        $watcher->start();
    }
}