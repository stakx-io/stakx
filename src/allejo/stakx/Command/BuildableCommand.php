<?php

/**
 * @copyright 2017 Vladimir Jimenez
 * @license   https://github.com/allejo/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Command;

use allejo\stakx\Configuration;
use allejo\stakx\System\Filesystem;
use allejo\stakx\Website;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class BuildableCommand.
 *
 * This abstract class handles configuring the website object
 */
abstract class BuildableCommand extends Command
{
    /**
     * @var Configuration
     */
    protected $configuration;

    /**
     * @var Website
     */
    protected $website;

    /**
     * @var Filesystem
     */
    protected $fs;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->fs = new Filesystem();

        $this->addOption('conf', 'c', InputOption::VALUE_REQUIRED, 'The configuration file to be used', $this->fs->absolutePath(Configuration::DEFAULT_NAME));
        $this->addOption('safe', 's', InputOption::VALUE_NONE, 'Disable file system access from Twig');
        $this->addOption('no-conf', 'l', InputOption::VALUE_NONE, 'Build a Stakx website without a configuration file');
        $this->addOption('no-clean', 'x', InputOption::VALUE_NONE, "Don't clean the _site before recompiling the website");
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->website = new Website($output);
        $this->website->setConfLess($input->getOption('no-conf'));
        $this->website->setNoClean($input->getOption('no-clean'));
    }

    /**
     * Configure the website builder.
     *
     * @param InputInterface $input
     */
    protected function configureBuild(InputInterface $input)
    {
        $this->website->setConfiguration($input->getOption('conf'));
        $this->website->setSafeMode($input->getOption('safe'));
    }
}
