<?php

declare(strict_types=1);

namespace App\Cruding\Service\Crud\Resource;

use App\Cruding\Dto\Resource\CrudRouteContext;

/**
 * Reads explicitly declared route-map services without deriving legacy service FQCNs.
 */
final class CrudResourceServiceClassResolver
{
    /**
     * @return list<class-string|non-empty-string>
     */
    public function candidates(CrudRouteContext $context): array
    {
        $candidates = [];
        $routeMapService = $this->routeMapService($context);
        if (null !== $routeMapService) {
            $candidates[] = $routeMapService;
        }

        foreach ($context->providerKeys as $providerKey) {
            $fqcn = $this->providerKeyToServiceClass($providerKey, $context->operation);
            if (null !== $fqcn) {
                $candidates[] = $fqcn;
            }
        }

        return array_values(array_unique($candidates));
    }

    public function providerKeyToServiceClass(string $providerKey, string $operation): ?string
    {
        return null;
    }

    private function routeMapService(CrudRouteContext $context): ?string
    {
        if (!is_array($context->routeMapEntry)) {
            return null;
        }

        $service = $context->routeMapEntry['service'] ?? null;

        return is_string($service) && '' !== $service ? $service : null;
    }

    private function normalizeOperation(string $operation): string
    {
        return match ($operation) {
            'detail', 'view' => 'show',
            default => '' !== $operation ? $operation : 'index',
        };
    }

    private function isActionToken(string $token): bool
    {
        return in_array($token, [
            'index', 'show', 'detail', 'view', 'card', 'table', 'gallery', 'compact', 'full', 'list',
            'new', 'create', 'edit', 'update', 'delete', 'bulk', 'import', 'export', 'archive', 'restore', 'duplicate',
            'attach', 'detach', 'assign', 'unassign', 'sync', 'audit', 'guard', 'map', 'validate', 'reload', 'clear', 'warmup',
            'health', 'status', 'diagnostic', 'enable', 'disable', 'impersonate',
        ], true);
    }

    private function pascal(string $token): string
    {
        $special = [
            'api' => 'Api',
            'id' => 'Id',
            'sku' => 'Sku',
            'ui' => 'Ui',
            'url' => 'Url',
        ];

        $parts = preg_split('/[^a-zA-Z0-9]+/', $token) ?: [$token];
        $result = '';
        foreach ($parts as $part) {
            if ('' === $part) {
                continue;
            }

            $lower = strtolower($part);
            $result .= $special[$lower] ?? ucfirst($lower);
        }

        return $result;
    }
}
