<?php

declare(strict_types=1);

namespace App\Cruding\Service\Crud;

/**
 * Protects classic CRUD identity routes from stealing reserved business and operation tokens.
 */
final readonly class CrudReservedRouteTokenPolicy
{
    /**
     * @param list<string> $surfaceTokens
     * @param list<string> $operationTokens
     */
    public function __construct(
        private array $surfaceTokens,
        private array $operationTokens,
    ) {
    }

    public function reasonForIdentityToken(string $token): ?string
    {
        $normalized = $this->normalize($token);
        if ('' === $normalized) {
            return null;
        }

        if ($this->contains($this->surfaceTokens, $normalized)) {
            return 'reserved_surface_token_not_routed';
        }

        if ($this->contains($this->operationTokens, $normalized)) {
            return 'reserved_operation_token_not_routed';
        }

        return null;
    }

    /**
     * @return list<string>
     */
    public function surfaceTokens(): array
    {
        return $this->normalizeList($this->surfaceTokens);
    }

    /**
     * @return list<string>
     */
    public function operationTokens(): array
    {
        return $this->normalizeList($this->operationTokens);
    }

    private function contains(array $tokens, string $needle): bool
    {
        return in_array($needle, $this->normalizeList($tokens), true);
    }

    /**
     * @param list<string> $tokens
     *
     * @return list<string>
     */
    private function normalizeList(array $tokens): array
    {
        $normalized = [];
        foreach ($tokens as $token) {
            $value = $this->normalize($token);
            if ('' === $value) {
                continue;
            }

            $normalized[$value] = $value;
        }

        return array_values($normalized);
    }

    private function normalize(string $token): string
    {
        return str_replace('_', '-', strtolower(trim($token)));
    }
}
