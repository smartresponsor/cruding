<?php

declare(strict_types=1);

namespace App\Cruding\Service\Crud\Resource;

use App\Cruding\Dto\Resource\CrudRouteContext;

final class CrudResourceServiceResolver
{
    /** @var array<string, mixed>|null */
    private ?array $lastDiagnostics = null;

    public function __construct(
        private readonly CrudResourceServiceClassResolver $classResolver,
        private readonly CrudResourceServiceLocator $serviceLocator,
    ) {
    }

    public function resolve(CrudRouteContext $context): ?object
    {
        $diagnostics = [
            'strategy' => 'explicit_service_or_service_layer_short_name',
            'providerKeys' => $context->providerKeys,
            'expectedServices' => [],
            'expectedTypes' => [],
            'classExists' => [],
            'containerHas' => [],
            'matchedService' => null,
        ];

        foreach ($this->classResolver->candidates($context) as $serviceClass) {
            $diagnostics['expectedServices'][] = $serviceClass;
            $diagnostics['classExists'][$serviceClass] = class_exists($serviceClass);
            $diagnostics['containerHas'][$serviceClass] = $this->serviceLocator->has($serviceClass);

            if (!$this->serviceLocator->has($serviceClass)) {
                continue;
            }

            $diagnostics['matchedService'] = $serviceClass;
            $this->lastDiagnostics = $this->normalizeDiagnostics($diagnostics);

            return $this->serviceLocator->get($serviceClass);
        }

        foreach ($this->classResolver->candidates($context) as $serviceClass) {
            $serviceId = $this->serviceLocator->uniqueServiceIdByShortClassName(
                $this->shortClassName($serviceClass),
                $this->namespaceRootPrefixes([$serviceClass]),
            );
            if (null === $serviceId) {
                continue;
            }

            $diagnostics['containerHas'][$serviceId] = $this->serviceLocator->has($serviceId);
            if (!$this->serviceLocator->has($serviceId)) {
                continue;
            }

            $diagnostics['matchedService'] = $serviceId;
            $this->lastDiagnostics = $this->normalizeDiagnostics($diagnostics);

            return $this->serviceLocator->get($serviceId);
        }

        $this->lastDiagnostics = $this->normalizeDiagnostics($diagnostics);

        return null;
    }

    /**
     * @return array<string, mixed>
     */
    public function lastDiagnostics(): array
    {
        return $this->lastDiagnostics ?? [];
    }

    /**
     * @param list<string> $candidateClassNames
     *
     * @return list<string>
     */
    private function namespaceRootPrefixes(array $candidateClassNames): array
    {
        $prefixes = [];
        foreach ($candidateClassNames as $candidateClassName) {
            $position = strpos($candidateClassName, '\\Service\\');
            if (false === $position) {
                continue;
            }

            $prefixes[] = substr($candidateClassName, 0, $position).'\\Service\\';
        }

        return array_values(array_unique($prefixes));
    }

    private function shortClassName(string $serviceClass): string
    {
        $serviceClass = trim($serviceClass, '\\');
        $position = strrpos($serviceClass, '\\');

        return false === $position ? $serviceClass : substr($serviceClass, $position + 1);
    }

    /**
     * @param array<string, mixed> $diagnostics
     *
     * @return array<string, mixed>
     */
    private function normalizeDiagnostics(array $diagnostics): array
    {
        $diagnostics['expectedServices'] = array_values(array_unique($diagnostics['expectedServices']));
        $diagnostics['expectedTypes'] = array_values(array_unique($diagnostics['expectedTypes']));

        return $diagnostics;
    }
}
