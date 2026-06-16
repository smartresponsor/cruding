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
 * Collects canonical host and component HTTP entrypoint services.
 */
final class CrudSurfaceServiceLocatorPass implements CompilerPassInterface
{
    private const HTTP_SERVICE_PATTERN = '/^App\\\\(?:[A-Z][A-Za-z0-9]*\\\\)?Service\\\\Http\\\\(?:[A-Z][A-Za-z0-9]*\\\\)*[A-Z][A-Za-z0-9]*Service$/D';

    public function process(ContainerBuilder $container): void
    {
        $references = [];

        foreach ($container->getDefinitions() as $id => $definition) {
            if ($definition->isAbstract() || $definition->isSynthetic()) {
                continue;
            }

            if (!$this->isHttpEntrypointService($id, $definition)) {
                continue;
            }

            $references[$id] = new Reference($id);
        }

        foreach ($container->getAliases() as $id => $alias) {
            $id = (string) $id;
            $target = (string) $alias;
            if (!$this->isCanonicalHttpService($id) && !$this->isCanonicalHttpService($target)) {
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

    private function isHttpEntrypointService(string $id, Definition $definition): bool
    {
        $class = $definition->getClass();
        if (is_string($class) && '' !== $class) {
            return $this->isCanonicalHttpService($class);
        }

        return $this->isCanonicalHttpService($id);
    }

    private function isCanonicalHttpService(string $serviceId): bool
    {
        return 1 === preg_match(self::HTTP_SERVICE_PATTERN, $serviceId);
    }
}
