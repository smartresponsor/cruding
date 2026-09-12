<?php

declare(strict_types=1);

namespace App\Cruding\Parser;

/**
 * Provides the resource path parser responsibility within the Cruding component.
 */
final class CrudResourcePathParser
{
    /**      * Executes the normalize operation.      */
    public function normalize(string $resourcePath): string
    {
        $trimmed = trim($resourcePath, '/');
        $collapsed = preg_replace('{/+}', '/', $trimmed);

        return str_replace('_', '-', strtolower((string) $collapsed));
    }

    /**
     * @return list<string>
     */
    public function segments(string $resourcePath): array
    {
        $normalized = $this->normalize($resourcePath);
        if ('' === $normalized) {
            return [];
        }

        return array_values(array_filter(explode('/', $normalized)));
    }

    /**      * Executes the tail operation.      */
    public function tail(string $resourcePath): string
    {
        $segments = $this->segments($resourcePath);

        return [] === $segments ? '' : (string) end($segments);
    }
}
