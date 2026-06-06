<?php

declare(strict_types=1);

namespace App\Cruding\Service\Runtime;

/**
 * Normalizes comma-separated runtime tokens into URL-safe lowercase token lists.
 */
final class CrudRuntimeTokenNormalizer
{
    /**
     * @return list<string>
     */
    public function csvToTokenList(?string $value): array
    {
        if (null === $value || '' === trim($value)) {
            return [];
        }

        $tokens = [];
        foreach (preg_split('/[,\s]+/', $value) ?: [] as $rawToken) {
            $token = $this->normalizeToken($rawToken);
            if (null === $token) {
                continue;
            }

            $tokens[$token] = $token;
        }

        return array_values($tokens);
    }

    /**
     * @param list<string> $tokens
     *
     * @return list<string>
     */
    public function normalizeTokenList(array $tokens): array
    {
        $normalized = [];
        foreach ($tokens as $token) {
            $value = $this->normalizeToken($token);
            if (null === $value) {
                continue;
            }

            $normalized[$value] = $value;
        }

        return array_values($normalized);
    }

    public function normalizeToken(string $token): ?string
    {
        $value = strtolower(trim($token));
        $value = str_replace(' ', '-', $value);
        $value = preg_replace('/[^a-z0-9_-]+/', '-', $value) ?: '';
        $value = trim(preg_replace('/[-_]{2,}/', '-', $value) ?: $value, '-_');

        if ('' === $value || !preg_match('/^[a-z0-9][a-z0-9_-]*$/', $value)) {
            return null;
        }

        return $value;
    }

    /**
     * @param list<string> $tokens
     */
    public function alternationRequirement(array $tokens): string
    {
        $tokens = $this->normalizeTokenList($tokens);
        if ([] === $tokens) {
            return '(?!)';
        }

        usort($tokens, static fn (string $left, string $right): int => strlen($right) <=> strlen($left) ?: $left <=> $right);

        return '(?:'.implode('|', array_map(static fn (string $token): string => preg_quote($token, '#'), $tokens)).')';
    }
}
