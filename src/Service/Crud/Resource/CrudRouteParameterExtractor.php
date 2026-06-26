<?php

declare(strict_types=1);

namespace App\Cruding\Service\Crud\Resource;

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

            if (is_scalar($value) || null === $value) {
                $parameters[(string) $key] = is_bool($value) ? (int) $value : $value;
            }
        }

        return $parameters;
    }
}
