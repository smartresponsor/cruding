<?php

declare(strict_types=1);

namespace App\Cruding\Resolver;

use App\Cruding\DTO\CrudContextDTO;
use App\Cruding\DTO\Entrypoint\CrudServiceContextDTO;
use App\Cruding\DTO\Entrypoint\CrudServiceResolutionDTO;
use App\Cruding\Service\CrudDefaultServiceRegistry;
use App\Cruding\Service\CrudPassiveService;
use App\Cruding\Service\Resource\CrudResourceServiceLocator;
use App\Cruding\ServiceInterface\Entrypoint\CrudServiceInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Resolves service resolver for Cruding request processing.
 */
final readonly class CrudServiceResolver
{
    public function __construct(
        private CrudExplicitServiceResolver $explicitServiceResolver,
        private CrudServiceClassNameResolver $classNameResolver,
        private CrudResourceServiceLocator $serviceLocator,
        private CrudDefaultServiceRegistry $defaultRegistry,
    ) {
    }

    /**      * Executes the resolve operation.      */
    public function resolve(Request $request, CrudContextDTO $context): CrudServiceResolutionDTO
    {
        $candidateServiceIds = $this->explicitServiceResolver->candidateServiceIds($request, $context);
        $candidateClassNames = $this->classNameResolver->candidateClassNames($context);
        $candidateShortClassNames = $this->classNameResolver->candidateShortClassNames($context);
        $namespaceRootPrefixes = $this->classNameResolver->candidateServiceNamespaceRootPrefixes($context);
        $classExists = [];
        $containerHas = [];

        foreach ($candidateServiceIds as $serviceId) {
            $containerHas[$serviceId] = $this->serviceLocator->has($serviceId);
            if (!$containerHas[$serviceId]) {
                continue;
            }

            return new CrudServiceResolutionDTO(
                service: $this->normalize($this->serviceLocator->get($serviceId)),
                status: CrudServiceResolutionDTO::STATUS_REGISTERED_SERVICE,
                serviceId: $serviceId,
                candidateServiceIds: $candidateServiceIds,
                candidateClassNames: $candidateClassNames,
                classExists: $classExists,
                containerHas: $containerHas,
            );
        }

        foreach ($candidateShortClassNames as $shortClassName) {
            $serviceId = $this->serviceLocator->uniqueServiceIdByShortClassName($shortClassName, $namespaceRootPrefixes);
            if (null === $serviceId) {
                continue;
            }

            $containerHas[$serviceId] = $this->serviceLocator->has($serviceId);
            if (!$containerHas[$serviceId]) {
                continue;
            }

            return new CrudServiceResolutionDTO(
                service: $this->normalize($this->serviceLocator->get($serviceId)),
                status: CrudServiceResolutionDTO::STATUS_URI_DERIVED_SERVICE,
                serviceId: $serviceId,
                candidateServiceIds: $candidateServiceIds,
                candidateClassNames: $candidateClassNames,
                classExists: $classExists,
                containerHas: $containerHas,
            );
        }

        $fallbackReason = CrudServiceResolutionDTO::STATUS_MISSING;

        $defaultService = $this->defaultRegistry->for($context);

        return new CrudServiceResolutionDTO(
            service: $defaultService,
            status: CrudServiceResolutionDTO::STATUS_DEFAULT_SERVICE,
            serviceId: $defaultService::class,
            fallbackReason: $fallbackReason,
            candidateServiceIds: $candidateServiceIds,
            candidateClassNames: $candidateClassNames,
            classExists: $classExists,
            containerHas: $containerHas,
        );
    }

    private function normalize(object $service): object
    {
        if ($service instanceof CrudServiceInterface) {
            return $service;
        }

        foreach (CrudServiceContextDTO::SUPPORTED_HTTP_METHODS as $method) {
            if (is_callable([$service, $method])) {
                return $service;
            }
        }

        if (is_callable([$service, 'isGrounded'])) {
            return $service;
        }

        if (is_callable($service)) {
            return $service;
        }

        return new CrudPassiveService($service);
    }
}
