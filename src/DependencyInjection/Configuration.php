<?php

declare(strict_types=1);

namespace App\Cruding\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

final class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('cruding');

        $treeBuilder->getRootNode()
            ->children()
                ->scalarNode('resource_path_requirement')
                    ->defaultValue('(?!.*(?:^|/)(?:new|edit|delete|audit|visibility|attach|detach)(?:$|/))[a-z0-9](?:[a-z0-9/_-]*[a-z0-9])?')
                ->end()
                ->arrayNode('capability_map')
                    ->useAttributeAsKey('name')
                    ->variablePrototype()->end()
                    ->defaultValue([
                        'identifiable' => [
                            'interfaces' => [
                                'App\Cruding\Contract\Capability\IdentifiableInterface',
                            ],
                            'methods_any' => ['getId'],
                            'properties_any' => ['id'],
                        ],
                        'sluggable' => [
                            'interfaces' => [
                                'App\Cruding\Contract\Capability\SluggableInterface',
                            ],
                            'methods_any' => ['getSlug'],
                            'properties_any' => ['slug'],
                        ],
                    ])
                ->end()
                ->arrayNode('entity_class_alias_map')
                    ->useAttributeAsKey('resource_path')
                    ->scalarPrototype()->end()
                    ->defaultValue([])
                ->end()
                ->arrayNode('form_type_map')
                    ->useAttributeAsKey('entity_class')
                    ->scalarPrototype()->end()
                    ->defaultValue([])
                ->end()
            ->end();

        return $treeBuilder;
    }
}
