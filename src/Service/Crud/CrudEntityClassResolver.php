<?php

declare(strict_types=1);

namespace App\Cruding\Service\Crud;

use App\Cruding\Exception\Crud\CrudResourceNotFoundException;
use Doctrine\Persistence\ManagerRegistry;

final readonly class CrudEntityClassResolver
{
    /**
     * @param array<string, class-string> $entityClassAliasMap
     */
    public function __construct(
        private ManagerRegistry $managerRegistry,
        private CrudResourcePathParser $resourcePathParser,
        private array $entityClassAliasMap = [],
    ) {
    }

    public function resolve(string $resourcePath): string
    {
        $entityClass = $this->tryResolve($resourcePath);
        if (null !== $entityClass) {
            return $entityClass;
        }

        throw CrudResourceNotFoundException::forResourcePath($resourcePath);
    }

    public function tryResolve(string $resourcePath): ?string
    {
        $candidateMap = $this->buildCandidateMap();

        foreach ($this->buildLookupKeys($resourcePath) as $lookupKey) {
            $explicitAliasClass = $this->entityClassAliasMap[$lookupKey] ?? null;
            if (is_string($explicitAliasClass) && '' !== $explicitAliasClass) {
                return $explicitAliasClass;
            }

            if (isset($candidateMap[$lookupKey])) {
                return $candidateMap[$lookupKey];
            }

            $tail = $this->resourcePathParser->tail($lookupKey);
            if ('' !== $tail && isset($candidateMap[$tail])) {
                return $candidateMap[$tail];
            }
        }

        return null;
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
        $parts = explode('\\Entity\\', $class, 2);
        $tail = $parts[1] ?? preg_replace('{^.*\\\\}', '', $class) ?? $class;
        $rawSegments = array_values(array_filter(explode('\\', $tail)));

        if ([] === $rawSegments) {
            return [];
        }

        $segments = $this->buildResourceSegments($rawSegments);
        if ([] === $segments) {
            return [];
        }

        $keys = [];
        $keys[] = implode('/', $segments);
        $keys[] = (string) end($segments);
        $keys[] = $this->normalizeEntityAlias($class);

        return array_values(array_unique(array_filter($keys, static fn (mixed $value): bool => is_string($value) && '' !== $value)));
    }

    /**
     * @param list<string> $rawSegments
     *
     * @return list<string>
     */
    private function buildResourceSegments(array $rawSegments): array
    {
        $lastIndex = array_key_last($rawSegments);
        $segments = [];

        foreach ($rawSegments as $index => $segment) {
            if ($index !== $lastIndex) {
                $segments[] = $this->normalizeSegment($segment);
                continue;
            }

            $shortName = preg_replace('/Entity$/', '', $segment) ?? $segment;
            $parentName = [] === $segments ? '' : (string) end($segments);
            $parentStudly = str_replace(' ', '', ucwords(str_replace('-', ' ', $parentName)));

            if ('' !== $parentStudly && str_starts_with($shortName, $parentStudly) && $shortName !== $parentStudly) {
                $shortName = substr($shortName, strlen($parentStudly));
            }

            $segments[] = $this->normalizeSegment($shortName);
        }

        return array_values(array_filter($segments, static fn (string $segment): bool => '' !== $segment));
    }

    private function normalizeEntityAlias(string $class): string
    {
        $base = preg_replace('{^.*\\\\}', '', $class) ?? $class;
        $base = preg_replace('/Entity$/', '', $base) ?? $base;

        return $this->normalizeSegment($base);
    }

    private function normalizeSegment(string $segment): string
    {
        $withHyphen = strtolower((string) preg_replace('/(?<!^)[A-Z]/', '-$0', $segment));

        return str_replace('_', '-', $withHyphen);
    }
}
