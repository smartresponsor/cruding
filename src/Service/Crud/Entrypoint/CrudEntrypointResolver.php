<?php

declare(strict_types=1);

namespace App\Cruding\Service\Crud\Entrypoint;

use App\Cruding\Dto\Crud\CrudContext;
use App\Cruding\Dto\Crud\Entrypoint\CrudEntrypointContext;
use App\Cruding\Dto\Crud\Entrypoint\CrudEntrypointResolution;
use App\Cruding\Service\Crud\Surface\CrudSurfaceServiceLocator;
use App\Cruding\ServiceInterface\Crud\Entrypoint\CrudEntrypointServiceInterface;
use Symfony\Component\HttpFoundation\Request;

final readonly class CrudEntrypointResolver
{
    public function __construct(
        private CrudEntrypointExplicitServiceResolver $explicitServiceResolver,
        private CrudEntrypointClassNameResolver $classNameResolver,
        private CrudSurfaceServiceLocator $serviceLocator,
        private NullCrudEntrypointService $nullService,
    ) {
    }

    public function resolve(Request $request, CrudContext $context): CrudEntrypointResolution
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

            return new CrudEntrypointResolution(
                service: $this->normalize($this->serviceLocator->get($serviceId)),
                status: CrudEntrypointResolution::STATUS_REGISTERED_SERVICE,
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

            return new CrudEntrypointResolution(
                service: $this->normalize($this->serviceLocator->get($className)),
                status: CrudEntrypointResolution::STATUS_URI_DERIVED_SERVICE,
                serviceId: $className,
                candidateServiceIds: $candidateServiceIds,
                candidateClassNames: $candidateClassNames,
                classExists: $classExists,
                containerHas: $containerHas,
            );
        }

        foreach ($candidateClassNames as $className) {
            if (($classExists[$className] ?? false) && !($containerHas[$className] ?? false)) {
                return new CrudEntrypointResolution(
                    service: $this->nullService,
                    status: CrudEntrypointResolution::STATUS_CLASS_EXISTS_BUT_NOT_REGISTERED,
                    serviceId: $className,
                    candidateServiceIds: $candidateServiceIds,
                    candidateClassNames: $candidateClassNames,
                    classExists: $classExists,
                    containerHas: $containerHas,
                );
            }
        }

        return new CrudEntrypointResolution(
            service: $this->nullService,
            status: CrudEntrypointResolution::STATUS_MISSING,
            serviceId: null,
            candidateServiceIds: $candidateServiceIds,
            candidateClassNames: $candidateClassNames,
            classExists: $classExists,
            containerHas: $containerHas,
        );
    }

    private function normalize(object $service): object
    {
        if ($service instanceof CrudEntrypointServiceInterface) {
            return $service;
        }

        foreach (CrudEntrypointContext::SUPPORTED_HTTP_METHODS as $method) {
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

        return new PassiveCrudEntrypointService($service);
    }
}
