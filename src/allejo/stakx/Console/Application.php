<?php

/**
 * @copyright 2018 Vladimir Jimenez
 * @license   https://github.com/stakx-io/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Console;

use allejo\stakx\Console\Command\BuildCommand;
use Symfony\Component\Console\Application as BaseApplication;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\Container;

/**
 * The base application class for stakx.
 */
class Application extends BaseApplication
{
    /** @var bool */
    private $safeMode;
    /** @var bool */
    private $useCache;
    /** @var Container */
    private $container;

    /**
     * {@inheritdoc}
     */
    public function run(InputInterface $input = null, OutputInterface $output = null)
    {
        $input = new ArgvInput();
        $this->handleApplicationFlags($input);

        $this->loadContainer([
            'parameters' => [
                'root_dir' => __DIR__ . '/../',
            ],
        ]);

        $output = $this->getContainer()->get('output');

        if (extension_loaded('xdebug') && !getenv('STAKX_DISABLE_XDEBUG_WARN'))
        {
            $output->writeln('<fg=black;bg=yellow>You are running Stakx with xdebug enabled. This has a major impact on runtime performance.</>');
        }

        return parent::run($input, $output);
    }

    /**
     * {@inheritdoc}
     */
    protected function getDefaultCommands()
    {
        $commands = parent::getDefaultCommands();

        $commands[] = new BuildCommand();

        return $commands;
    }

    ///
    // Application Settings
    ///

    /**
     * Get whether or not the application is being run in safe mode.
     *
     * @return bool
     */
    public function inSafeMode()
    {
        return (bool)$this->safeMode;
    }

    /**
     * Set safe mode for the application.
     *
     * @param bool $safeMode
     */
    public function setSafeMode($safeMode)
    {
        $this->safeMode = $safeMode;
    }

    /**
     * Get whether or not to look for and use the application cache.
     *
     * @return bool
     */
    public function useCache()
    {
        return (bool)$this->useCache;
    }

    /**
     * Set whether or not to use an existing cache.
     *
     * @param bool $useCache
     */
    public function setUseCache($useCache)
    {
        $this->useCache = $useCache;
    }

    /**
     * Handle application wide flags.
     *
     * @param InputInterface $input
     */
    private function handleApplicationFlags(InputInterface $input)
    {
        $this->setUseCache($input->hasParameterOption('--use-cache'));
        $this->setSafeMode($input->hasParameterOption('--safe'));
    }

    ///
    // Container Settings
    ///

    /**
     * Get the Service container.
     *
     * @return Container
     */
    public function getContainer()
    {
        return $this->container;
    }

    /**
     * Load the cached application container or build a new one.
     *
     * @param array $containerOptions
     *
     * @throws \Exception
     */
    private function loadContainer(array $containerOptions)
    {
        $builder = new ContainerBuilder($containerOptions);

        require $builder->build();

        $this->container = new \ProjectServiceContainer();
    }
}
