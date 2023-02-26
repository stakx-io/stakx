<?php

/**
 * @copyright 2018 Vladimir Jimenez
 * @license   https://github.com/stakx-io/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Console\Command;

use allejo\stakx\Configuration;
use allejo\stakx\Exception\FileAwareException;
use allejo\stakx\Filesystem\File;
use allejo\stakx\Filesystem\FilesystemLoader as fs;
use allejo\stakx\RuntimeStatus;
use allejo\stakx\Service;
use allejo\stakx\Utilities\StrUtils;
use allejo\stakx\Website;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class BuildCommand extends ContainerAwareCommand
{
    const NO_CONF = 'no-conf';
    const NO_CLEAN = 'no-clean';
    const USE_DRAFTS = 'use-drafts';
    const WATCHING = 'watching';
    const USE_CACHE = 'use-cache';
    const SAFE_MODE = 'safe';
    const BUILD_PROFILE = 'profile';

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->addOption(self::NO_CONF, 'l', InputOption::VALUE_NONE, 'Build a stakx website without a configuration file');

        $this->addOption('conf', 'c', InputOption::VALUE_REQUIRED, 'The configuration file to be used', Configuration::DEFAULT_NAME);
        $this->addOption(self::SAFE_MODE, 's', InputOption::VALUE_NONE, 'Disable file system access from Twig');
        $this->addOption(self::NO_CLEAN, 'x', InputOption::VALUE_NONE, "Don't clean the _site before recompiling the website");
        $this->addOption(self::USE_DRAFTS, 'd', InputOption::VALUE_NONE, 'Publish all ContentItems marked as drafts');
        $this->addOption(self::USE_CACHE, null, InputOption::VALUE_NONE, 'Use the existing cache folder for building the website');
        $this->addOption(self::BUILD_PROFILE, null, InputOption::VALUE_NONE, 'Display a profile of Twig templates');

        $this->setName('build');
        $this->setDescription('Builds the stakx website');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->handleDeprecations($input, $output);
        $this->setRunTimeOptions($input);

        try
        {
            $this->configureConfigurationFile($input);

            /** @var Website $website */
            $website = $this->getContainer()->get(Website::class);
            $website->build();

            $output->writeln(sprintf('Your site built successfully! It can be found at: %s',
                $website->getConfiguration()->getTargetFolder()
            ));

            return 0;
        }
        catch (FileAwareException $e)
        {
            $output->writeln(StrUtils::interpolate(
                "Your website failed to build with the following error in file '{file}'{line}: {message}", [
                    'file' => $e->getPath(),
                    'line' => (($l = $e->getLineNumber()) >= 0) ? ' on line ' . $l : '',
                    'message' => $e->getMessage(),
                ]
            ));
        }
        catch (\Exception $e)
        {
            $output->writeln(sprintf('Your website failed to build with the following error: %s',
                $e->getMessage()
            ));
        }

        return 1;
    }

    /**
     * @param InputInterface $input
     *
     * @throws \Exception
     */
    protected function configureConfigurationFile(InputInterface $input)
    {
        $confFilePath = $input->getOption('conf');
        $siteRoot = fs::getFolderPath(realpath($confFilePath));
        Service::setWorkingDirectory($siteRoot);

        $configFile = new File($confFilePath);

        /** @var Configuration $conf */
        $conf = $this->getContainer()->get(Configuration::class);
        $conf->parse($configFile);
    }

    protected function setRunTimeOptions(InputInterface $input)
    {
        if ($input->getOption(self::NO_CLEAN))
        {
            Service::setRuntimeFlag(RuntimeStatus::BOOT_WITHOUT_CLEAN);
        }

        if ($input->getOption(self::USE_DRAFTS))
        {
            Service::setRuntimeFlag(RuntimeStatus::USING_DRAFTS);
        }

        if ($input->getOption(self::USE_CACHE))
        {
            Service::setRuntimeFlag(RuntimeStatus::USING_CACHE);
        }

        if ($input->getOption(self::SAFE_MODE))
        {
            // Caches could be maliciously manipulated so disable reading the cache in safe mode
            Service::removeRuntimeFlag(RuntimeStatus::USING_CACHE);

            Service::setRuntimeFlag(RuntimeStatus::IN_SAFE_MODE);
        }

        if ($input->getOption(self::BUILD_PROFILE))
        {
            Service::setRuntimeFlag(RuntimeStatus::IN_PROFILE_MODE);
        }
    }

    protected function handleDeprecations(InputInterface $input, OutputInterface $output)
    {
        if ($input->getOption(self::NO_CONF))
        {
            $output->writeln('Deprecation: The "--no-conf" option is no longer supported. You must have a configuration file.');
        }
    }
}
