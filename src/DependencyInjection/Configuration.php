<?php

namespace Drivenow\AsyncWorkersBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html#cookbook-bundles-extension-config-class}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('async_workers');

        $rootNode
            ->useAttributeAsKey('worker')
                ->prototype('array')
                    ->children()
                        ->scalarNode('num_processes')
                            ->defaultValue(1)
                        ->end()
                        ->scalarNode('max_memory_usage')
                            ->defaultValue('256M')
                        ->end()
                        ->scalarNode('service_name')
                            ->isRequired()
                            ->cannotBeEmpty()
                        ->end()
                        ->scalarNode('timeout_per_seconds')
                            ->defaultValue(1)
                        ->end()
                        ->scalarNode('max_execution_time')
                            ->defaultValue('1 day')
                        ->end()
                        ->scalarNode('per_select')
                            ->defaultValue(10)
                        ->end()
                        ->scalarNode('shard_max')
                            ->defaultValue(1)
                        ->end()
                        ->scalarNode('shard_num')
                             ->defaultValue(1)
                        ->end()
                        ->scalarNode('iterations')
                            ->defaultValue(1000)
                        ->end()
                    ->end()
                ->end()
    ;

        return $treeBuilder;
    }
}
