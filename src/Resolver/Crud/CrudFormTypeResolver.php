<?php

declare(strict_types=1);

namespace App\Cruding\Resolver\Crud;

final class CrudFormTypeResolver
{
    /**
     * @param array<class-string, class-string> $formTypeMap
     */
    public function __construct(private readonly array $formTypeMap = [])
    {
    }

    public function resolve(string $entityClass): ?string
    {
        if (isset($this->formTypeMap[$entityClass]) && class_exists($this->formTypeMap[$entityClass])) {
            return $this->formTypeMap[$entityClass];
        }

        $entityParts = explode('\\Entity\\', $entityClass, 2);
        if (!isset($entityParts[1])) {
            return null;
        }

        $namespaceRoot = $entityParts[0];
        $entityTail = $entityParts[1];
        $segments = explode('\\', $entityTail);
        $shortName = (string) end($segments);
        $parentSegments = $segments;
        array_pop($parentSegments);

        $candidates = [
            sprintf('%s\\Form\\%sType', $namespaceRoot, $shortName),
        ];

        if ([] !== $parentSegments) {
            $candidates[] = sprintf('%s\\Form\\%s\\%sType', $namespaceRoot, implode('\\', $parentSegments), $shortName);
        }

        foreach ($candidates as $candidate) {
            if (class_exists($candidate)) {
                return $candidate;
            }
        }

        return null;
    }
}
