<?php

declare(strict_types=1);

namespace App\Cruding\Service\Resource;

/**
 * Holds host and component service-layer entries collected at compile time.
 */
final readonly class CrudResourceServiceLocator
{
    /** @var array<string, \Closure(): object> */
    private array $serviceFactoryById;

    /** @var array<string, list<array{serviceId: string, candidates: list<string>}>> */
    private array $shortClassNameIndex;

    /**
     * @param iterable<string, \Closure(): object> $serviceFactories
     * @param array<string, string>                $serviceTypes
     */
    public function __construct(
        iterable $serviceFactories,
        private array $serviceTypes = [],
    ) {
        $this->serviceFactoryById = iterator_to_array($serviceFactories);
        $this->shortClassNameIndex = $this->buildShortClassNameIndex();
    }

    /**      * Executes the has operation.      */
    public function has(string $serviceClass): bool
    {
        return isset($this->serviceFactoryById[$serviceClass]);
    }

    /**      * Executes the get operation.      */
    public function get(string $serviceClass): object
    {
        $factory = $this->serviceFactoryById[$serviceClass] ?? null;
        if (null === $factory) {
            throw new \RuntimeException(sprintf('View service "%s" is not registered.', $serviceClass));
        }

        return $factory();
    }

    /**
     * @return list<string>
     */
    public function serviceIds(): array
    {
        $ids = array_keys($this->serviceFactoryById);
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
        foreach ($this->serviceTypes as $serviceId => $providedService) {
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
