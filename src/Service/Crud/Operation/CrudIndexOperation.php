<?php

declare(strict_types=1);

namespace App\Cruding\Service\Crud\Operation;

use App\Cruding\Dto\Crud\CrudContext;
use App\Cruding\Service\Crud\CrudNotFoundResponseFactory;
use App\Cruding\Service\Crud\Entrypoint\CrudEntrypointOperationRunner;
use App\Cruding\Service\Crud\Surface\CrudSurfaceContractFactory;
use App\Cruding\ServiceInterface\Crud\CrudContextResolverInterface;
use App\Cruding\ServiceInterface\Crud\CrudPageDefinitionProviderInterface;
use App\Cruding\ServiceInterface\Crud\Operation\CrudIndexOperationInterface;
use App\Cruding\Value\Surface\CrudSurfaceContract;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final readonly class CrudIndexOperation implements CrudIndexOperationInterface
{
    public function __construct(
        private CrudContextResolverInterface $contextResolver,
        private CrudPageDefinitionProviderInterface $pageDefinitionProvider,
        private CrudSurfaceContractFactory $surfaceContractFactory,
        private CrudNotFoundResponseFactory $notFoundResponseFactory,
        private CrudEntrypointOperationRunner $entrypointRunner,
    ) {
    }

    public function handle(Request $request): Response|CrudSurfaceContract
    {
        $entrypointResult = $this->tryExplicitRouteEntrypoint($request);
        if (null !== $entrypointResult) {
            return $entrypointResult;
        }

        $context = $this->contextResolver->tryResolve($request);
        if (null === $context) {
            return $this->notFoundResponseFactory->create($request, 'crud_context_not_found');
        }

        $entrypointResult = $this->entrypointRunner->tryRun($request, $context);
        if (null !== $entrypointResult) {
            return $entrypointResult;
        }

        return $this->surfaceContractFactory->create($this->pageDefinitionProvider->provideIndex($context));
    }

    private function tryExplicitRouteEntrypoint(Request $request): Response|CrudSurfaceContract|null
    {
        $routeService = $request->attributes->get('_crud_service');
        if (!is_string($routeService) || '' === trim($routeService)) {
            return null;
        }

        $context = new CrudContext(
            surface: (string) $request->attributes->get('_crud_surface', 'public'),
            operation: (string) $request->attributes->get('_crud_operation', 'index'),
            resourcePath: (string) $request->attributes->get('resourcePath', ''),
            entityClass: '',
            identifierField: 'slug',
            identifierValue: null,
            formTypeClass: null,
        );

        return $this->entrypointRunner->tryRun($request, $context);
    }
}
