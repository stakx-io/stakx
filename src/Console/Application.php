<?php

/**
 * @copyright 2018 Vladimir Jimenez
 * @license   https://github.com/stakx-io/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Console;

use allejo\stakx\Console\Command\BuildCommand;
use allejo\stakx\Console\Command\ServeCommand;
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
    private bool $safeMode;
    private bool $useCache;
    private Container $container;

    public function run(InputInterface $input = null, OutputInterface $output = null): int
    {
        $input = $input ?? new ArgvInput();
        $this->handleApplicationFlags($input);

        $this->loadContainer([
            'parameters' => [
                'root_dir' => __DIR__ . '/../',
            ],
        ]);

        $output = $output ?? $this->getContainer()->get('output');

        if (extension_loaded('xdebug') && !getenv('STAKX_DISABLE_XDEBUG_WARN'))
        {
            $output->writeln('<fg=black;bg=yellow>You are running Stakx with xdebug enabled. This has a major impact on runtime performance.</>');
        }

        return parent::run($input, $output);
    }

    /**
     * {@inheritdoc}
     */
    protected function getDefaultCommands(): array
    {
        $commands = parent::getDefaultCommands();

        $commands[] = new BuildCommand();
        $commands[] = new ServeCommand();

        return $commands;
    }

    ///
    // Application Settings
    ///

    /**
     * Get whether the application is being run in safe mode.
     */
    public function inSafeMode(): bool
    {
        return $this->safeMode;
    }

    /**
     * Set safe mode for the application.
     */
    public function setSafeMode(bool $safeMode): void
    {
        $this->safeMode = $safeMode;
    }

    /**
     * Get whether to look for and use the application cache.
     */
    public function useCache(): bool
    {
        return $this->useCache;
    }

    /**
     * Set whether to use an existing cache.
     */
    public function setUseCache(bool $useCache): void
    {
        $this->useCache = $useCache;
    }

    /**
     * Handle application wide flags.
     */
    private function handleApplicationFlags(InputInterface $input): void
    {
        $this->setUseCache($input->hasParameterOption('--use-cache'));
        $this->setSafeMode($input->hasParameterOption('--safe'));
    }

    ///
    // Container Settings
    ///

    /**
     * Get the Service container.
     */
    public function getContainer(): Container
    {
        return $this->container;
    }

    /**
     * Load the cached application container or build a new one.
     *
     * @throws \Exception
     */
    private function loadContainer(array $containerOptions): void
    {
        $builder = new ContainerBuilder($containerOptions);

        require $builder->build();

        // The `ProjectServiceContainer` class is generated at compile time by
        // Symfony's Dependency Injection component.
        $this->container = new \ProjectServiceContainer();
    }
}
