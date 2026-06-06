<?php

declare(strict_types=1);

namespace App\Cruding\Service\Surface;

use Symfony\Component\HttpFoundation\Request;

/**
 * Converts a route template or path into normalized positional route segment values.
 */
final readonly class CrudRouteSegmentReader
{
    public function __construct(
        private CrudRouteValueNormalizer $normalizer,
    ) {
    }

    /**
     * @return list<string>
     */
    public function segments(string $path): array
    {
        $path = trim($path, '/');
        if ('' === $path) {
            return [];
        }

        return array_values(array_filter(explode('/', $path), static fn (string $segment): bool => '' !== $segment));
    }

    /**
     * @param list<string> $segments
     *
     * @return list<array{value: string|int|null, dynamic: bool, name: ?string}>
     */
    public function segmentValues(array $segments, Request $request): array
    {
        $values = [];
        foreach ($segments as $segment) {
            $placeholder = $this->normalizer->placeholderName($segment);
            if (null !== $placeholder) {
                $values[] = [
                    'value' => $this->normalizer->scalarValue($request->attributes->get($placeholder)),
                    'dynamic' => true,
                    'name' => $placeholder,
                ];
                continue;
            }

            $values[] = [
                'value' => $segment,
                'dynamic' => false,
                'name' => null,
            ];
        }

        return array_values(array_filter(
            $values,
            static fn (array $value): bool => null !== $value['value'] && '' !== (string) $value['value'],
        ));
    }
}
