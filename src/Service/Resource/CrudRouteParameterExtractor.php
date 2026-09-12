<?php

declare(strict_types=1);

namespace App\Cruding\Service\Resource;

use Symfony\Component\HttpFoundation\Request;

/**
 * Extracts public scalar route attributes into the route context.
 */
final readonly class CrudRouteParameterExtractor
{
    /**
     * @return array<string, string|int|null>
     */
    public function routeParameters(Request $request): array
    {
        $parameters = [];
        foreach ($request->attributes->all() as $key => $value) {
            if (str_starts_with((string) $key, '_')) {
                continue;
            }

            if (is_int($value) || is_string($value) || null === $value) {
                $parameters[(string) $key] = $value;
            } elseif (is_bool($value)) {
                $parameters[(string) $key] = (int) $value;
            } elseif (is_float($value)) {
                $parameters[(string) $key] = (string) $value;
            }
        }

        return $parameters;
    }
}
