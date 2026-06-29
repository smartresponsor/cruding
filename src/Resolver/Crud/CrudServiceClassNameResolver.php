<?php

declare(strict_types=1);

namespace App\Cruding\Resolver\Crud;

use App\Cruding\Dto\Crud\CrudContext;

final class CrudServiceClassNameResolver
{
    /**
     * @return list<class-string|non-empty-string>
     */
    public function candidateClassNames(CrudContext $context): array
    {
        return [];
    }

    /**
     * @return list<non-empty-string>
     */
    public function candidateShortClassNames(CrudContext $context): array
    {
        $segments = $this->resourceSegments($context->resourcePath);
        if ([] === $segments) {
            return [];
        }

        $operation = $this->pascal('' !== $context->operation ? $context->operation : 'index');
        $root = $this->pascal($segments[0]);
        $tail = array_map($this->pascal(...), array_slice($segments, 1));

        $class = $root.implode('', $tail).$operation.'Service';
        $localClass = [] !== $tail ? implode('', $tail).$operation.'Service' : null;

        return array_values(array_unique(array_filter([$class, $localClass])));
    }

    /**
     * @return list<non-empty-string>
     */
    public function candidateServiceIds(CrudContext $context): array
    {
        return [];
    }

    /**
     * @return list<non-empty-string>
     */
    public function candidateServiceNamespaceRootPrefixes(CrudContext $context): array
    {
        $namespaceRoots = [];
        $componentNamespace = $this->componentNamespace($context->entityClass);
        if (null !== $componentNamespace) {
            $namespaceRoots[] = 'App\\'.$componentNamespace;
        }
        $namespaceRoots[] = 'App';

        $prefixes = [];
        foreach (array_values(array_unique($namespaceRoots)) as $namespaceRoot) {
            $prefixes[] = $namespaceRoot.'\\Service\\';
        }

        return array_values(array_unique($prefixes));
    }

    /** @return list<string> */
    private function resourceSegments(string $resourcePath): array
    {
        $segments = [];
        foreach (explode('/', trim($resourcePath, '/')) as $segment) {
            $segment = trim($segment);
            if ('' === $segment) {
                continue;
            }

            $segments[] = $segment;
        }

        return $segments;
    }

    private function componentNamespace(string $entityClass): ?string
    {
        if (1 !== preg_match('/^App\\\\(?<component>[A-Z][A-Za-z0-9]*)\\\\(?:Entity|Model|Domain)\\\\/', ltrim($entityClass, '\\'), $match)) {
            return null;
        }

        return $match['component'];
    }

    private function pascal(string $token): string
    {
        $special = [
            'api' => 'Api',
            'id' => 'Id',
            'sku' => 'Sku',
            'ui' => 'Ui',
            'url' => 'Url',
        ];

        $parts = preg_split('/[^a-zA-Z0-9]+/', $token) ?: [$token];
        $result = '';
        foreach ($parts as $part) {
            if ('' === $part) {
                continue;
            }

            $lower = strtolower($part);
            $result .= $special[$lower] ?? ucfirst($lower);
        }

        return $result;
    }
}
