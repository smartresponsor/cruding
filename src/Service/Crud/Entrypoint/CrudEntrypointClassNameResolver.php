<?php

declare(strict_types=1);

namespace App\Cruding\Service\Crud\Entrypoint;

use App\Cruding\Dto\Crud\CrudContext;

final class CrudEntrypointClassNameResolver
{
    /**
     * @return list<class-string|non-empty-string>
     */
    public function candidateClassNames(CrudContext $context): array
    {
        $segments = $this->resourceSegments($context->resourcePath);
        if ([] === $segments) {
            return [];
        }

        $operation = $this->pascal('' !== $context->operation ? $context->operation : 'index');
        $root = $this->pascal($segments[0]);
        $tail = array_map($this->pascal(...), array_slice($segments, 1));
        $all = [$root, ...$tail];

        $namespace = 'App\\Service\\Http\\'.implode('\\', $all);
        $class = $root.implode('', $tail).$operation.'Service';

        $candidates = [$namespace.'\\'.$class];

        if ([] !== $tail) {
            $localClass = implode('', $tail).$operation.'Service';
            $candidates[] = $namespace.'\\'.$localClass;
        }

        return array_values(array_unique($candidates));
    }

    /**
     * @return list<non-empty-string>
     */
    public function candidateServiceIds(CrudContext $context): array
    {
        return $this->candidateClassNames($context);
    }

    /**
     * @return list<string>
     */
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
