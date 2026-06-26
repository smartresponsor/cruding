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
                    ->defaultValue('[a-z][a-z0-9_-]*(?:/(?!(?:new|edit|delete|audit|visibility|attach|detach)$)[a-z0-9][a-z0-9_-]*)*')
                ->end()
                ->arrayNode('route_guard')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('runtime_scope_env')->defaultValue('APP_RUNTIME_SCOPE')->end()
                        ->scalarNode('runtime_entity_env')->defaultValue('APP_RUNTIME_ENTITY')->end()
                        ->scalarNode('runtime_view_token_env')->defaultValue('APP_RUNTIME_VIEW_TOKEN')->end()
                        ->scalarNode('runtime_reserved_env')->defaultValue('APP_RUNTIME_RESERVED')->end()
                        ->arrayNode('reserved_tokens')
                            ->scalarPrototype()->end()
                            ->defaultValue([])
                        ->end()
                        ->arrayNode('view_tokens')
                            ->scalarPrototype()->end()
                            ->defaultValue([])
                        ->end()
                        ->arrayNode('operation_tokens')
                            ->scalarPrototype()->end()
                            ->defaultValue([])
                        ->end()
                        ->arrayNode('resource_path_reserved_tokens')
                            ->scalarPrototype()->end()
                            ->defaultValue([])
                        ->end()
                        ->scalarNode('runtime_lock_glob')->defaultValue('config/kernel/runtime_scope.{env}.lock.php')->end()
                        ->booleanNode('require_runtime_lock')->defaultFalse()->end()
                        ->booleanNode('require_composer_packages')->defaultFalse()->end()
                        ->arrayNode('scope_package_map')
                            ->useAttributeAsKey('scope_token')
                            ->scalarPrototype()->end()
                            ->defaultValue([
                                'cruding' => 'cruding/crud',
                                'viewing' => 'viewing/view',
                                'interfacing' => 'interfacing/interface',
                                'administering' => 'administering/administer',
                                'accessing' => 'accessing/access',
                            ])
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('capability_map')
                    ->useAttributeAsKey('nameEntity')
                    ->variablePrototype()->end()
                    ->defaultValue([
                        'identifiable' => [
                            'interfaces' => [
                                'App\Cruding\Contract\Capability\CrudIdentifiableInterface',
                            ],
                            'methods_any' => ['getId'],
                            'properties_any' => ['id'],
                        ],
                        'sluggable' => [
                            'interfaces' => [
                                'App\Cruding\Contract\Capability\CrudSluggableInterface',
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
