<?php

declare(strict_types=1);

namespace App\Cruding\Resolver;

use App\Cruding\DTO\CrudContextDTO;
use Symfony\Component\HttpFoundation\Request;

/**
 * Resolves explicit service resolver for Cruding request processing.
 */
final class CrudExplicitServiceResolver
{
    /**
     * @return list<string>
     */
    public function candidateServiceIds(Request $request, CrudContextDTO $context): array
    {
        $candidates = [];

        foreach (['_crud_entrypoint_service', '_crud_service', '_crud_handler_service', 'crud_service'] as $attribute) {
            $value = $request->attributes->get($attribute);
            if (is_string($value) && '' !== trim($value)) {
                $candidates[] = trim($value);
            }
        }

        $routeKey = $request->attributes->get('_crud_route_key');
        if (is_string($routeKey) && '' !== trim($routeKey)) {
            $candidates[] = trim($routeKey);
        }

        return array_values(array_unique($candidates));
    }
}
