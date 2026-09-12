<?php

declare(strict_types=1);

namespace App\Cruding\Resolver\Resource;

use App\Cruding\DTO\Resource\CrudRouteContextDTO;

/**
 * Reads explicitly declared route-map services without deriving legacy service FQCNs.
 */
final class CrudResourceServiceClassResolver
{
    /** @return list<string> */
    public function candidates(CrudRouteContextDTO $context): array
    {
        $candidates = [];
        $routeMapService = $this->routeMapService($context);
        if (null !== $routeMapService) {
            $candidates[] = $routeMapService;
        }

        return array_values(array_unique($candidates));
    }

    private function routeMapService(CrudRouteContextDTO $context): ?string
    {
        if (!is_array($context->routeMapEntry)) {
            return null;
        }

        $service = $context->routeMapEntry['service'] ?? null;

        return is_string($service) && '' !== $service ? $service : null;
    }
}
