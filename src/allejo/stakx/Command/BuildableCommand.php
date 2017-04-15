<?php

/**
 * @copyright 2017 Vladimir Jimenez
 * @license   https://github.com/allejo/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Command;

use allejo\stakx\Configuration;
use allejo\stakx\Service;
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
    const NO_CONF = 'no-conf';
    const NO_CLEAN = 'no-clean';
    const USE_DRAFTS = 'use-drafts';
    const WATCHING = 'watching';
    const CLEAN_CACHE = 'clean-cache';
    const SAFE_MODE = 'safe';
    const BUILD_PROFILE = 'profile';

    /** @var Configuration */
    protected $configuration;

    /** @var Website */
    protected $website;

    /** @var Filesystem */
    protected $fs;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->fs = new Filesystem();

        $this->addOption('conf', 'c', InputOption::VALUE_REQUIRED, 'The configuration file to be used', $this->fs->absolutePath(Configuration::DEFAULT_NAME));
        $this->addOption(self::SAFE_MODE, 's', InputOption::VALUE_NONE, 'Disable file system access from Twig');
        $this->addOption(self::NO_CONF, 'l', InputOption::VALUE_NONE, 'Build a stakx website without a configuration file');
        $this->addOption(self::NO_CLEAN, 'x', InputOption::VALUE_NONE, "Don't clean the _site before recompiling the website");
        $this->addOption(self::USE_DRAFTS, 'd', InputOption::VALUE_NONE, 'Publish all ContentItems marked as drafts');
        $this->addOption(self::CLEAN_CACHE, null, InputOption::VALUE_NONE, 'Clean the stakx cache folder');
        $this->addOption(self::BUILD_PROFILE, null, InputOption::VALUE_NONE, 'Display a profile of Twig templates');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->website = new Website($output);
        $this->website->setConfLess($input->getOption(self::NO_CONF));

        $flags = array(
            self::SAFE_MODE, self::NO_CONF, self::NO_CLEAN,
            self::USE_DRAFTS, self::CLEAN_CACHE, self::BUILD_PROFILE,
        );

        foreach ($flags as $flag)
        {
            $this->setServiceParameter($input, $flag);
        }
    }

    /**
     * Configure the website builder.
     *
     * @param InputInterface $input
     */
    protected function configureBuild(InputInterface $input)
    {
        $this->website->setConfiguration($input->getOption('conf'));
    }

    /**
     * Set a parameter to the Service singleton
     *
     * @param InputInterface $input
     * @param string         $param
     */
    private function setServiceParameter(InputInterface &$input, $param)
    {
        Service::setParameter($param, $input->getOption($param));
    }
}
