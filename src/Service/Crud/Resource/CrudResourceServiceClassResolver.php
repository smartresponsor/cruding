<?php

declare(strict_types=1);

namespace App\Cruding\Service\Crud\Resource;

use App\Cruding\Dto\Resource\CrudRouteContext;

/**
 * Converts canonical Cruding provider keys into Symfony-style HTTP service FQCNs.
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
        $tokens = array_values(array_filter(explode('.', trim($providerKey)), static fn (string $token): bool => '' !== $token));
        if ([] === $tokens) {
            return null;
        }

        $normalizedOperation = $this->normalizeOperation($operation);
        $lastToken = $tokens[array_key_last($tokens)];
        if (!$this->isActionToken($lastToken)) {
            $tokens[] = $normalizedOperation;
        }

        if (count($tokens) < 2) {
            return null;
        }

        $namespaceTokens = array_slice($tokens, 0, -1);
        $classTokens = $tokens;

        $namespace = implode('\\', array_map($this->pascal(...), $namespaceTokens));
        $class = implode('', array_map($this->pascal(...), $classTokens)).'Service';

        return 'App\\Service\\Http\\'.$namespace.'\\'.$class;
    }

    public function expectedTypeClass(string $serviceClass): ?string
    {
        if (!str_starts_with($serviceClass, 'App\\Service\\Http\\')) {
            return null;
        }

        $suffix = substr($serviceClass, strlen('App\\Service\\Http\\'));
        if (false === $suffix || '' === $suffix) {
            return null;
        }

        if (!str_ends_with($suffix, 'Service')) {
            return null;
        }

        return 'App\\Form\\'.substr($suffix, 0, -7).'Type';
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
