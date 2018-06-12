<?php

namespace TM\JsonApiBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\NodeBuilder;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder()
    {
        $tb = new TreeBuilder();

        $root = $tb
            ->root('tm_json_api', 'array')
                ->children()
        ;

        $this->addMetadataSection($root);

        return $tb;
    }

    private function addMetadataSection(NodeBuilder $builder)
    {
        $builder
            ->arrayNode('metadata')
                ->addDefaultsIfNotSet()
                ->fixXmlConfig('directory', 'directories')
                ->children()

                    ->arrayNode('directories')
                        ->useAttributeAsKey('name')
                        ->prototype('array')
                            ->children()
                                ->scalarNode('path')->isRequired()->end()
                                ->scalarNode('namespace_prefix')->defaultValue('')->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;
    }
}
