<?php

declare(strict_types=1);

namespace App\Cruding\DependencyInjection\Compiler;

use App\Cruding\Service\Surface\CrudSurfaceServiceLocator;
use Symfony\Component\DependencyInjection\Argument\ServiceLocatorArgument;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Collects concrete App\*\Service\Http\* services so Cruding can resolve route-owned surfaces by FQCN convention.
 */
final class CrudSurfaceServiceLocatorPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $references = [];

        foreach ($container->getDefinitions() as $id => $definition) {
            if ($definition->isAbstract() || $definition->isSynthetic()) {
                continue;
            }

            if (!$this->isHttpSurfaceService($id, $definition)) {
                continue;
            }

            $references[$id] = new Reference($id);
        }

        foreach ($container->getAliases() as $id => $alias) {
            $id = (string) $id;
            if (!str_starts_with($id, 'App\\') || !str_contains($id, '\\Service\\Http\\')) {
                continue;
            }

            $references[$id] = new Reference($id);
        }

        ksort($references);

        if (!$container->hasDefinition(CrudSurfaceServiceLocator::class)) {
            $container->setDefinition(CrudSurfaceServiceLocator::class, new Definition(CrudSurfaceServiceLocator::class));
        }

        $container->getDefinition(CrudSurfaceServiceLocator::class)
            ->setArgument(0, new ServiceLocatorArgument($references));
    }

    private function isHttpSurfaceService(string $id, Definition $definition): bool
    {
        if (str_starts_with($id, 'App\\') && str_contains($id, '\\Service\\Http\\')) {
            return true;
        }

        $class = $definition->getClass();

        return is_string($class) && str_starts_with($class, 'App\\') && str_contains($class, '\\Service\\Http\\');
    }
}
