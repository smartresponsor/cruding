<?php

declare(strict_types=1);

namespace App\Cruding\Resolver;

use App\Cruding\Exception\CrudResourceNotFoundException;
use App\Cruding\Parser\CrudResourcePathParser;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Resolves entity class resolver for Cruding request processing.
 */
final class CrudEntityClassResolver
{
    /** @var array<string, class-string>|null */
    private ?array $candidateMap = null;

    /**
     * @param array<string, class-string> $entityClassAliasMap
     */
    public function __construct(
        private readonly ManagerRegistry $managerRegistry,
        private readonly CrudResourcePathParser $resourcePathParser,
        private readonly array $entityClassAliasMap = [],
    ) {
    }

    /**      * Executes the resolve operation.      */
    public function resolve(string $resourcePath): string
    {
        $entityClass = $this->tryResolve($resourcePath);
        if (null !== $entityClass) {
            return $entityClass;
        }

        throw CrudResourceNotFoundException::forResourcePath($resourcePath);
    }

    /**      * Executes the try resolve operation.      */
    public function tryResolve(string $resourcePath): ?string
    {
        $lookupKeys = $this->buildLookupKeys($resourcePath);

        foreach ($lookupKeys as $lookupKey) {
            $explicitAliasClass = $this->entityClassAliasMap[$lookupKey] ?? null;
            if (is_string($explicitAliasClass)) {
                return $explicitAliasClass;
            }
        }

        $candidateMap = $this->candidateMap ??= $this->buildCandidateMap();

        foreach ($lookupKeys as $lookupKey) {
            if (isset($candidateMap[$lookupKey])) {
                return $candidateMap[$lookupKey];
            }
        }

        return null;
    }

    /**
     * Builds the canonical entity short name from every business token in the resource path.
     */
    public function canonicalEntityShortName(string $resourcePath): string
    {
        $segments = $this->resourcePathParser->segments($resourcePath);
        if ([] === $segments) {
            return '';
        }

        return implode('', array_map($this->studly(...), $segments)).'Entity';
    }

    /**
     * @return list<string>
     */
    private function buildLookupKeys(string $resourcePath): array
    {
        $keys = [$this->resourcePathParser->normalize($resourcePath)];

        if (str_contains($resourcePath, '_')) {
            $keys[] = $this->resourcePathParser->normalize(str_replace('_', '/', $resourcePath));
        }

        return array_values(array_unique(array_filter($keys, static fn (string $key): bool => '' !== $key)));
    }

    /**
     * @return array<string, class-string>
     */
    private function buildCandidateMap(): array
    {
        $candidates = [];

        foreach ($this->managerRegistry->getManagers() as $manager) {
            $metadataFactory = $manager->getMetadataFactory();
            foreach ($metadataFactory->getAllMetadata() as $metadata) {
                $class = $metadata->getName();
                foreach ($this->buildKeys($class) as $key) {
                    $candidates[$key] ??= $class;
                }
            }
        }

        return $candidates;
    }

    /**
     * @return list<string>
     */
    private function buildKeys(string $class): array
    {
        $base = preg_replace('{^.*\\\\}', '', $class) ?? $class;
        $base = preg_replace('/Entity$/', '', $base) ?? $base;

        $key = $this->normalizeStudlyPath($base);
        if ('' === $key) {
            return [];
        }

        return [$key];
    }

    private function normalizeStudlyPath(string $value): string
    {
        $tokens = preg_split('/(?=[A-Z])/', $value, -1, PREG_SPLIT_NO_EMPTY);
        if (!is_array($tokens)) {
            return '';
        }

        return implode('/', array_map(
            static fn (string $token): string => str_replace('_', '-', strtolower($token)),
            $tokens,
        ));
    }

    private function studly(string $segment): string
    {
        return str_replace(' ', '', ucwords(str_replace(['-', '_'], ' ', $segment)));
    }
}
