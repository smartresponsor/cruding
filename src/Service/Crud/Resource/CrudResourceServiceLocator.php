<?php

declare(strict_types=1);

namespace App\Cruding\Service\Crud\Resource;

use Psr\Container\ContainerInterface;

/**
 * Holds host and component service-layer entries collected at compile time.
 */
final readonly class CrudResourceServiceLocator
{
    /** @var array<string, list<array{serviceId: string, candidates: list<string>}>> */
    private array $shortClassNameIndex;

    public function __construct(
        private ContainerInterface $locator,
    ) {
        $this->shortClassNameIndex = $this->buildShortClassNameIndex();
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
        foreach ($this->shortClassNameIndex[$shortClassName] ?? [] as $entry) {
            foreach ($entry['candidates'] as $candidate) {
                if (!$this->matchesNamespaceRootPrefix($candidate, $namespaceRootPrefixes)) {
                    continue;
                }

                $matches[] = $entry['serviceId'];
                break;
            }
        }

        $matches = array_values(array_unique($matches));

        return 1 === count($matches) ? $matches[0] : null;
    }

    /** @return array<string, list<array{serviceId: string, candidates: list<string>}>> */
    private function buildShortClassNameIndex(): array
    {
        $index = [];
        foreach ($this->providedServices() as $serviceId => $providedService) {
            $candidates = array_values(array_unique(array_map(
                static fn (string $candidate): string => ltrim($candidate, '?'),
                [(string) $serviceId, (string) $providedService],
            )));

            foreach ($candidates as $candidate) {
                $shortClassName = $this->shortClassName($candidate);
                $index[$shortClassName][] = [
                    'serviceId' => (string) $serviceId,
                    'candidates' => $candidates,
                ];
            }
        }

        return $index;
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
