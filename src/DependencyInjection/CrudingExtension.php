<?php

declare(strict_types=1);

namespace App\Cruding\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

final class CrudingExtension extends Extension implements PrependExtensionInterface
{
    /**
     * @param list<array<string, mixed>> $configs
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $loader = new YamlFileLoader($container, new FileLocator(\dirname(__DIR__, 2).'/config'));
        $loader->load('services.yaml');

        $configuration = new Configuration();
        /** @var array{
         *     resource_path_requirement: string,
         *     capability_map: array<string, mixed>,
         *     entity_class_alias_map: array<string, string>,
         *     form_type_map: array<string, string>
         * } $config
         */
        $config = $this->processConfiguration($configuration, $configs);

        $container->setParameter('cruding.resource_path_requirement', $config['resource_path_requirement']);
        $container->setParameter('cruding.capability_map', $config['capability_map']);
        $container->setParameter('cruding.entity_class_alias_map', $config['entity_class_alias_map']);
        $container->setParameter('cruding.form_type_map', $config['form_type_map']);
    }

    public function prepend(ContainerBuilder $container): void
    {
        if (!$container->hasExtension('twig')) {
            return;
        }

        $container->prependExtensionConfig('twig', [
            'paths' => [
                \dirname(__DIR__, 2).'/templates' => 'Cruding',
            ],
        ]);
    }

    public function getAlias(): string
    {
        return 'cruding';
    }
}
