<?php

/**
 * @copyright 2017 Vladimir Jimenez
 * @license   https://github.com/allejo/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\DependencyInjection\Compiler;

use allejo\stakx\DataTransformer\DataTransformer;
use allejo\stakx\DataTransformer\DataTransformerManager;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class DataTransformerPass implements CompilerPassInterface
{
    /**
     * You can modify the container here before it is dumped to PHP code.
     *
     * @param ContainerBuilder $container
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->has(DataTransformerManager::class))
        {
            return;
        }

        $definition = $container->findDefinition(DataTransformerManager::class);
        $services = $container->findTaggedServiceIds(DataTransformer::CONTAINER_TAG);

        foreach ($services as $id => $tags)
        {
            $definition->addMethodCall('addDataTransformer', [new Reference($id)]);
        }
    }
}
