<?php

declare(strict_types=1);

namespace App\Cruding\Service\Surface;

use App\Cruding\Dto\Surface\CrudRouteContext;

/**
 * Resolves a parsed Cruding surface to a canonical App\Service\Http\* service.
 */
final class CrudSurfaceServiceResolver
{
    /** @var array<string, mixed>|null */
    private ?array $lastDiagnostics = null;

    public function __construct(
        private readonly CrudSurfaceServiceClassResolver $classResolver,
        private readonly CrudSurfaceServiceLocator $serviceLocator,
    ) {
    }

    public function resolve(CrudRouteContext $context): ?object
    {
        $diagnostics = [
            'strategy' => 'providerKey_to_FQCN',
            'providerKeys' => $context->providerKeys,
            'expectedServices' => [],
            'expectedTypes' => [],
            'classExists' => [],
            'containerHas' => [],
            'matchedService' => null,
        ];

        foreach ($this->classResolver->candidates($context) as $serviceClass) {
            $diagnostics['expectedServices'][] = $serviceClass;
            $typeClass = $this->classResolver->expectedTypeClass($serviceClass);
            if (null !== $typeClass) {
                $diagnostics['expectedTypes'][] = $typeClass;
            }

            $diagnostics['classExists'][$serviceClass] = class_exists($serviceClass);
            $diagnostics['containerHas'][$serviceClass] = $this->serviceLocator->has($serviceClass);

            if (!$this->serviceLocator->has($serviceClass)) {
                continue;
            }

            $diagnostics['matchedService'] = $serviceClass;
            $this->lastDiagnostics = $this->normalizeDiagnostics($diagnostics);

            return $this->serviceLocator->get($serviceClass);
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
