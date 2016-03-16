<?php

namespace allejo\stakx\Command;

use allejo\stakx\Core\Configuration;
use allejo\stakx\Object\ContentItem;
use allejo\stakx\Environment\Filesystem;
use allejo\stakx\Object\Website;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class BuildCommand extends Command
{
    /**
     * @var Filesystem
     */
    protected $fs;

    /**
     * @var Configuration
     */
    protected $configuration;

    /**
     * @var Website
     */
    protected $website;

    /**
     * @var ContentItem[]
     */
    protected $collections;

    /**
     * @var string[]
     */
    protected $errors;

    protected function configure ()
    {
        $this->website = new Website();
        $this->fs = new Filesystem();

        $this->setName('build');
        $this->setDescription('Builds the stakx website');
        $this->addOption('conf', 'c', InputOption::VALUE_REQUIRED, 'The configuration file to be used', $this->fs->buildPath(getcwd(), Configuration::DEFAULT_NAME));
    }

    protected function execute (InputInterface $input, OutputInterface $output)
    {
        $this->makeCacheFolder();

        $this->website->setConfiguration($input->getOption('conf'));
        $this->website->build($this->errors);

        print_r($this->errors);
    }

    private function makeCacheFolder ()
    {
        if (!$this->fs->exists('.stakx-cache'))
        {
            $this->fs->mkdir('.stakx-cache/twig');
        }
    }
}