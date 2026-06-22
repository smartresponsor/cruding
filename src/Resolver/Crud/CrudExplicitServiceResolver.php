<?php

declare(strict_types=1);

namespace App\Cruding\Resolver\Crud;

use App\Cruding\Dto\Crud\CrudContext;
use Symfony\Component\HttpFoundation\Request;

final class CrudExplicitServiceResolver
{
    /**
     * @return list<non-empty-string>
     */
    public function candidateServiceIds(Request $request, CrudContext $context): array
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
