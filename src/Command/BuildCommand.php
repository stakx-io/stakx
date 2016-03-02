<?php

namespace allejo\stakx\Command;

use allejo\stakx\Core\Configuration;
use allejo\stakx\Utilities\FileSystemTrait;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class BuildCommand extends Command
{
    use FileSystemTrait;

    protected $configuration;

    protected function configure()
    {
        $this->setName('build');
        $this->setDescription('Builds the stakx website');

        $this->addOption('conf', 'c', InputOption::VALUE_REQUIRED, 'The configuration file to be used', $this->buildPath(getcwd(), Configuration::DEFAULT_NAME));
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->configuration = new Configuration($input->getOption('conf'));
        $folders = $this->ls();
    }
}