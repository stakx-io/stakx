<?php

/**
 * @copyright 2018 Vladimir Jimenez
 * @license   https://github.com/stakx-io/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Core;

use allejo\stakx\Configuration;
use allejo\stakx\DataTransformer\DataTransformer;
use allejo\stakx\Filesystem\FilesystemPath;
use allejo\stakx\MarkupEngine\MarkupEngine;
use allejo\stakx\Templating\Twig\Extension\TwigFilterInterface;
use allejo\stakx\Templating\Twig\Extension\TwigFunctionInterface;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Console\Application as BaseApplication;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Dumper\PhpDumper;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

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
     */
    private function loadContainer(array $containerOptions)
    {
        $cachedContainerPath = new FilesystemPath(getcwd() . '/' . Configuration::CACHE_FOLDER . '/container-cache.php');

        if (!$this->useCache() || !file_exists($cachedContainerPath))
        {
            $this->makeCacheDir();
            $this->buildContainer($cachedContainerPath, $containerOptions);
        }

        require $cachedContainerPath;

        $this->container = new \ProjectServiceContainer();
    }

    /**
     * Build and compile the application container.
     *
     * @param string $cachePath
     * @param array  $containerOptions
     */
    private function buildContainer($cachePath, array $containerOptions)
    {
        $container = new ContainerBuilder();

        foreach ($containerOptions['parameters'] as $key => $value)
        {
            $container->setParameter($key, $value);
        }

        $loader = new YamlFileLoader($container, new FileLocator(__DIR__ . '/../app/'));
        $loader->load('services.yml');

        $container
            ->registerForAutoconfiguration(DataTransformer::class)
            ->addTag(DataTransformer::CONTAINER_TAG)
        ;

        $container
            ->registerForAutoconfiguration(MarkupEngine::class)
            ->addTag(MarkupEngine::CONTAINER_TAG)
        ;

        $container
            ->registerForAutoconfiguration(TwigFilterInterface::class)
            ->addTag(TwigFilterInterface::CONTAINER_TAG)
        ;

        $container
            ->registerForAutoconfiguration(TwigFunctionInterface::class)
            ->addTag(TwigFunctionInterface::CONTAINER_TAG)
        ;

        $container->compile();
        $dumper = new PhpDumper($container);

        file_put_contents($cachePath, $dumper->dump());
    }

    /**
     * Create a cache directory if it doesn't exist.
     */
    private function makeCacheDir()
    {
        $cachedFolder = new FilesystemPath(getcwd() . '/' . Configuration::CACHE_FOLDER);

        if (!file_exists($cachedFolder))
        {
            mkdir($cachedFolder);
        }
    }
}
