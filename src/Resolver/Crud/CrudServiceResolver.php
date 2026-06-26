<?php

declare(strict_types=1);

namespace App\Cruding\Resolver\Crud;

use App\Cruding\Dto\Crud\CrudContext;
use App\Cruding\Dto\Crud\Entrypoint\CrudServiceContext;
use App\Cruding\Dto\Crud\Entrypoint\CrudServiceResolution;
use App\Cruding\Service\Crud\CrudDefaultServiceRegistry;
use App\Cruding\Service\Crud\Resource\CrudResourceServiceLocator;
use App\Cruding\ServiceInterface\Crud\Entrypoint\CrudServiceInterface;
use Symfony\Component\HttpFoundation\Request;

final readonly class CrudServiceResolver
{
    public function __construct(
        private CrudExplicitServiceResolver $explicitServiceResolver,
        private CrudServiceClassNameResolver $classNameResolver,
        private CrudResourceServiceLocator $serviceLocator,
        private CrudDefaultServiceRegistry $defaultRegistry,
    ) {
    }

    public function resolve(Request $request, CrudContext $context): CrudServiceResolution
    {
        $candidateServiceIds = $this->explicitServiceResolver->candidateServiceIds($request, $context);
        $candidateClassNames = $this->classNameResolver->candidateClassNames($context);
        $classExists = [];
        $containerHas = [];

        foreach ($candidateServiceIds as $serviceId) {
            $containerHas[$serviceId] = $this->serviceLocator->has($serviceId);
            if (!$containerHas[$serviceId]) {
                continue;
            }

            return new CrudServiceResolution(
                service: $this->normalize($this->serviceLocator->get($serviceId)),
                status: CrudServiceResolution::STATUS_REGISTERED_SERVICE,
                serviceId: $serviceId,
                candidateServiceIds: $candidateServiceIds,
                candidateClassNames: $candidateClassNames,
                classExists: $classExists,
                containerHas: $containerHas,
            );
        }

        foreach ($candidateClassNames as $className) {
            $classExists[$className] = class_exists($className);
            $containerHas[$className] = $this->serviceLocator->has($className);

            if (!$containerHas[$className]) {
                continue;
            }

            return new CrudServiceResolution(
                service: $this->normalize($this->serviceLocator->get($className)),
                status: CrudServiceResolution::STATUS_URI_DERIVED_SERVICE,
                serviceId: $className,
                candidateServiceIds: $candidateServiceIds,
                candidateClassNames: $candidateClassNames,
                classExists: $classExists,
                containerHas: $containerHas,
            );
        }

        $fallbackReason = CrudServiceResolution::STATUS_MISSING;
        foreach ($candidateClassNames as $className) {
            if (($classExists[$className] ?? false) && !($containerHas[$className] ?? false)) {
                $fallbackReason = CrudServiceResolution::STATUS_CLASS_EXISTS_BUT_NOT_REGISTERED;
                break;
            }
        }

        $defaultService = $this->defaultRegistry->for($context);

        return new CrudServiceResolution(
            service: $defaultService,
            status: CrudServiceResolution::STATUS_DEFAULT_SERVICE,
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

        foreach (CrudServiceContext::SUPPORTED_HTTP_METHODS as $method) {
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

        return new PassiveCrudService($service);
    }
}
