<?php

/**
 * @copyright 2018 Vladimir Jimenez
 * @license   https://github.com/stakx-io/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Console;

use allejo\stakx\AssetEngine\AssetEngineInterface;
use allejo\stakx\DataTransformer\DataTransformerInterface;
use allejo\stakx\MarkupEngine\MarkupEngineInterface;
use allejo\stakx\Templating\Twig\Extension\TwigFilterInterface;
use allejo\stakx\Templating\Twig\Extension\TwigFunctionInterface;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder as BaseBuilder;
use Symfony\Component\DependencyInjection\Dumper\PhpDumper;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\EventDispatcher\DependencyInjection\RegisterListenersPass;

class ContainerBuilder
{
    private $containerPath;
    private $options;

    public function __construct(array $options)
    {
        $this->containerPath = __DIR__ . '/Container.php';
        $this->options = $options;
    }

    public function build()
    {
        if (!$this->isPhar())
        {
            $this->compileAndDump();
        }

        return $this->containerPath;
    }

    private function isPhar()
    {
        return strlen(\Phar::running()) > 0;
    }

    private function compileAndDump()
    {
        $container = new BaseBuilder();
        $container
            ->addCompilerPass(new RegisterListenersPass())
        ;

        foreach ($this->options['parameters'] as $key => $value)
        {
            $container->setParameter($key, $value);
        }

        $loader = new YamlFileLoader($container, new FileLocator(__DIR__ . '/app/'));
        $loader->load('services.yml');

        $container
            ->registerForAutoconfiguration(AssetEngineInterface::class)
            ->addTag(AssetEngineInterface::CONTAINER_TAG)
        ;

        $container
            ->registerForAutoconfiguration(DataTransformerInterface::class)
            ->addTag(DataTransformerInterface::CONTAINER_TAG)
        ;

        $container
            ->registerForAutoconfiguration(MarkupEngineInterface::class)
            ->addTag(MarkupEngineInterface::CONTAINER_TAG)
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
        file_put_contents($this->containerPath, $dumper->dump());
    }
}
