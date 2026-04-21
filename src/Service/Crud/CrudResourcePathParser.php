<?php

declare(strict_types=1);

namespace App\Cruding\Service\Crud;

final class CrudResourcePathParser
{
    public function normalize(string $resourcePath): string
    {
        $trimmed = trim($resourcePath, '/');
        $collapsed = preg_replace('{/+}', '/', $trimmed);

        return strtolower((string) $collapsed);
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

    public function tail(string $resourcePath): string
    {
        $segments = $this->segments($resourcePath);

        return [] === $segments ? '' : (string) end($segments);
    }
}
