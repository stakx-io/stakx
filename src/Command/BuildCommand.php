<?php

namespace allejo\stakx\Command;

use allejo\stakx\Core\Configuration;
use allejo\stakx\Core\ContentItem;
use allejo\stakx\Utilities\StakxFilesystem;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Twig_Environment;
use Twig_Loader_Filesystem;

class BuildCommand extends Command
{
    /**
     * @var Twig_Environment
     */
    protected $twig;

    /**
     * @var StakxFilesystem
     */
    protected $fs;

    /**
     * @var Configuration
     */
    protected $configuration;

    /**
     * @var ContentItem[]
     */
    protected $collections;

    protected function configure ()
    {
        $this->fs = new StakxFilesystem();

        $this->setName('build');
        $this->setDescription('Builds the stakx website');
        $this->addOption('conf', 'c', InputOption::VALUE_REQUIRED, 'The configuration file to be used', $this->fs->buildPath(getcwd(), Configuration::DEFAULT_NAME));
    }

    protected function execute (InputInterface $input, OutputInterface $output)
    {
        $this->makeCacheFolder();
        $this->configureTwig();

        $this->configuration = new Configuration($input->getOption('conf'));

        $this->parseCollections();
    }

    private function parseCollections ()
    {
        $collections = $this->configuration->getCollectionsFolders();
        $this->collections = array();

        foreach ($collections as $collection)
        {
            $dataFiles = $this->fs->ls($collection['folder'])['files'];

            foreach ($dataFiles as $dataFile)
            {
                $this->collections[] = new ContentItem($dataFile);
            }
        }
    }

    private function configureTwig ()
    {
        $loader     = new Twig_Loader_Filesystem('.');
        $this->twig = new Twig_Environment($loader, array(
            'cache' => '.stakx-cache/twig'
        ));
    }

    private function makeCacheFolder ()
    {
        if (!$this->fs->exists('.stakx-cache'))
        {
            $this->fs->mkdir('.stakx-cache/twig');
        }
    }
}