<?php declare(strict_types=1);

/**
 * @copyright 2018 Vladimir Jimenez
 * @license   https://github.com/stakx-io/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Console;

use allejo\stakx\Console\Command\BuildCommand;
use allejo\stakx\Console\Command\ServeCommand;
use Exception;
use ProjectServiceContainer;
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

        if (extension_loaded('xdebug') && !getenv('STAKX_DISABLE_XDEBUG_WARN')) {
            $output->writeln('<fg=black;bg=yellow>You are running Stakx with xdebug enabled. This has a major impact on runtime performance.</>');
        }

        return parent::run($input, $output);
    }

    //
    // Application Settings
    //

    /**
     * Get whether or not the application is being run in safe mode.
     */
    public function inSafeMode(): bool
    {
        return (bool)$this->safeMode;
    }

    /**
     * Set safe mode for the application.
     *
     * @param bool $safeMode
     */
    public function setSafeMode($safeMode): void
    {
        $this->safeMode = $safeMode;
    }

    /**
     * Get whether or not to look for and use the application cache.
     */
    public function useCache(): bool
    {
        return (bool)$this->useCache;
    }

    /**
     * Set whether or not to use an existing cache.
     *
     * @param bool $useCache
     */
    public function setUseCache($useCache): void
    {
        $this->useCache = $useCache;
    }

    //
    // Container Settings
    //

    /**
     * Get the Service container.
     */
    public function getContainer(): Container
    {
        return $this->container;
    }

    /**
     * {@inheritdoc}
     */
    protected function getDefaultCommands()
    {
        $commands = parent::getDefaultCommands();

        $commands[] = new BuildCommand();
        $commands[] = new ServeCommand();

        return $commands;
    }

    /**
     * Handle application wide flags.
     */
    private function handleApplicationFlags(InputInterface $input): void
    {
        $this->setUseCache($input->hasParameterOption('--use-cache'));
        $this->setSafeMode($input->hasParameterOption('--safe'));
    }

    /**
     * Load the cached application container or build a new one.
     *
     * @throws Exception
     */
    private function loadContainer(array $containerOptions): void
    {
        $builder = new ContainerBuilder($containerOptions);

        require $builder->build();

        $this->container = new ProjectServiceContainer();
    }
}
