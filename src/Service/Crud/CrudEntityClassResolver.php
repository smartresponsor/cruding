<?php

declare(strict_types=1);

namespace App\Cruding\Service\Crud;

use App\Cruding\Exception\Crud\CrudResourceNotFoundException;
use Doctrine\Persistence\ManagerRegistry;

final readonly class CrudEntityClassResolver
{
    public function __construct(
        private ManagerRegistry $managerRegistry,
        private CrudResourcePathParser $resourcePathParser,
    ) {
    }

    public function resolve(string $resourcePath): string
    {
        $normalizedPath = $this->resourcePathParser->normalize($resourcePath);
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

        if (isset($candidates[$normalizedPath])) {
            return $candidates[$normalizedPath];
        }

        $tail = $this->resourcePathParser->tail($normalizedPath);
        if ('' !== $tail && isset($candidates[$tail])) {
            return $candidates[$tail];
        }

        throw CrudResourceNotFoundException::forResourcePath($resourcePath);
    }

    /**
     * @return list<string>
     */
    private function buildKeys(string $class): array
    {
        $parts = explode('\\Entity\\', $class, 2);
        $tail = $parts[1] ?? preg_replace('{^.*\\\\}', '', $class) ?? $class;
        $segments = array_values(array_filter(array_map([$this, 'normalizeSegment'], explode('\\', $tail))));

        if ([] === $segments) {
            return [];
        }

        $keys = [];
        $keys[] = implode('/', $segments);
        $keys[] = (string) end($segments);

        $deduplicated = [];
        foreach ($segments as $segment) {
            $last = [] === $deduplicated ? null : end($deduplicated);
            if ($segment !== $last) {
                $deduplicated[] = $segment;
            }
        }

        if ([] !== $deduplicated) {
            $keys[] = implode('/', $deduplicated);
            $keys[] = (string) end($deduplicated);
        }

        return array_values(array_unique(array_filter($keys, static fn (mixed $value): bool => is_string($value) && '' !== $value)));
    }

    private function normalizeSegment(string $segment): string
    {
        $withHyphen = strtolower((string) preg_replace('/(?<!^)[A-Z]/', '-$0', $segment));

        return str_replace('_', '-', $withHyphen);
    }
}
