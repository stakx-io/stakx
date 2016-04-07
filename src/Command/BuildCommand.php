<?php

namespace allejo\stakx\Command;

use allejo\stakx\Object\ContentItem;
use allejo\stakx\Object\Configuration;
use allejo\stakx\System\Filesystem;
use allejo\stakx\Object\Website;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Logger\ConsoleLogger;
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
        $this->fs = new Filesystem();

        $this->setName('build');
        $this->setDescription('Builds the stakx website');
        $this->addOption('conf', 'c', InputOption::VALUE_REQUIRED, 'The configuration file to be used', $this->fs->absolutePath(Configuration::DEFAULT_NAME));
    }

    protected function execute (InputInterface $input, OutputInterface $output)
    {
        $logger = new ConsoleLogger($output);
        $this->website = new Website($logger);

        $this->website->setConfiguration($input->getOption('conf'));
        $this->website->build();
    }

    /**
     * @internal
     *
     * @return \allejo\stakx\Object\Website
     */
    public function _getWebsite()
    {
        return $this->website;
    }
}