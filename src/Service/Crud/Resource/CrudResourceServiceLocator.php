<?php

declare(strict_types=1);

namespace App\Cruding\Service\Crud\Resource;

use Psr\Container\ContainerInterface;

/**
 * Holds host and component service-layer entries collected at compile time.
 */
final readonly class CrudResourceServiceLocator
{
    public function __construct(
        private ContainerInterface $locator,
    ) {
    }

    public function has(string $serviceClass): bool
    {
        return $this->locator->has($serviceClass);
    }

    public function get(string $serviceClass): object
    {
        $service = $this->locator->get($serviceClass);
        if (!is_object($service)) {
            throw new \RuntimeException(sprintf('Resolved view service "%s" is not an object.', $serviceClass));
        }

        return $service;
    }

    /**
     * @return list<string>
     */
    public function serviceIds(): array
    {
        if (!method_exists($this->locator, 'getProvidedServices')) {
            return [];
        }

        /** @var array<string, string> $provided */
        $provided = $this->locator->getProvidedServices();
        $ids = array_keys($provided);
        sort($ids);

        return $ids;
    }

    /**
     * @param list<string> $namespaceRootPrefixes
     */
    public function uniqueServiceIdByShortClassName(string $shortClassName, array $namespaceRootPrefixes = []): ?string
    {
        $shortClassName = trim($shortClassName, '\\');
        if ('' === $shortClassName) {
            return null;
        }

        $matches = [];
        foreach ($this->providedServices() as $serviceId => $providedService) {
            foreach (array_unique([$serviceId, $providedService]) as $candidate) {
                $candidate = ltrim((string) $candidate, '?');
                if (!$this->matchesNamespaceRootPrefix($candidate, $namespaceRootPrefixes)) {
                    continue;
                }

                if ($this->shortClassName($candidate) !== $shortClassName) {
                    continue;
                }

                $matches[] = $serviceId;
                break;
            }
        }

        $matches = array_values(array_unique($matches));

        return 1 === count($matches) ? $matches[0] : null;
    }

    /**
     * @return array<string, string>
     */
    private function providedServices(): array
    {
        if (!method_exists($this->locator, 'getProvidedServices')) {
            return [];
        }

        /** @var array<string, string> $provided */
        $provided = $this->locator->getProvidedServices();

        return $provided;
    }

    /**
     * @param list<string> $namespaceRootPrefixes
     */
    private function matchesNamespaceRootPrefix(string $serviceId, array $namespaceRootPrefixes): bool
    {
        if ([] === $namespaceRootPrefixes) {
            return true;
        }

        foreach ($namespaceRootPrefixes as $prefix) {
            if (str_starts_with($serviceId, $prefix)) {
                return true;
            }
        }

        return false;
    }

    private function shortClassName(string $serviceId): string
    {
        $serviceId = trim($serviceId, '\\');
        $position = strrpos($serviceId, '\\');

        return false === $position ? $serviceId : substr($serviceId, $position + 1);
    }
}
