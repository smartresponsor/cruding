<?php

declare(strict_types=1);

namespace App\Cruding\Service\Surface;

/**
 * Normalizes raw route values into canonical crud/surface tokens.
 */
final readonly class CrudRouteValueNormalizer
{
    public function placeholderName(string $segment): ?string
    {
        if (1 !== preg_match('/^\{([A-Za-z_][A-Za-z0-9_]*)\}$/', $segment, $matches)) {
            return null;
        }

        return $matches[1];
    }

    public function placeholderField(?string $name): ?string
    {
        if (null === $name || '' === $name) {
            return null;
        }

        return $name;
    }

    public function scalarValue(mixed $value): string|int|null
    {
        if (is_int($value)) {
            return $value;
        }

        if (is_string($value) && '' !== $value) {
            return ctype_digit($value) ? (int) $value : $value;
        }

        return null;
    }

    public function token(string $value): string
    {
        $value = trim($value);
        $value = preg_replace('/(?<!^)[A-Z]/', '-$0', $value) ?: $value;
        $value = strtolower(str_replace('_', '-', $value));
        $value = preg_replace('/[^a-z0-9\/-]+/', '-', $value) ?: $value;
        $value = trim(preg_replace('/-+/', '-', $value) ?: $value, '-/');

        return $value;
    }
}
