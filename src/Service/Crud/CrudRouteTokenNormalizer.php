<?php

declare(strict_types=1);

namespace App\Cruding\Service\Crud;

final class CrudRouteTokenNormalizer
{
    /**
     * @return list<string>
     */
    public function tokens(string $path): array
    {
        $trimmed = trim($path, "/ \t\n\r\0\x0B");
        if ('' === $trimmed) {
            return [];
        }

        $parts = explode('/', preg_replace('{/+}', '/', $trimmed) ?: $trimmed);
        $tokens = [];
        foreach ($parts as $part) {
            $token = $this->token($part);
            if ('' === $token) {
                continue;
            }

            $tokens[] = $token;
        }

        return $tokens;
    }

    public function token(string $token): string
    {
        return str_replace('_', '-', strtolower(trim($token)));
    }
}
