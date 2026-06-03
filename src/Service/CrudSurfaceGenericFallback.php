<?php

declare(strict_types=1);

namespace App\Cruding\Service;

use App\Cruding\Dto\Crud\CrudContext;
use App\Cruding\Dto\Surface\CrudRouteContext;
use App\Cruding\Service\Crud\CrudEntityClassResolver;
use App\Cruding\Service\Crud\CrudFormTypeResolver;
use App\Cruding\Service\Crud\CrudSurfaceContractFactory;
use App\Cruding\ServiceInterface\Crud\CrudAccessContextBuilderInterface;
use App\Cruding\ServiceInterface\Crud\CrudObjectFinderInterface;
use App\Cruding\ServiceInterface\Crud\CrudPageDefinitionProviderInterface;
use App\Cruding\Value\Surface\CrudSurfaceContract;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * Read-only fallback for standard resource index/detail routes.
 */
final readonly class CrudSurfaceGenericFallback
{
    public function __construct(
        private CrudEntityClassResolver $entityClassResolver,
        private CrudFormTypeResolver $formTypeResolver,
        private CrudObjectFinderInterface $objectFinder,
        private CrudAccessContextBuilderInterface $accessContextBuilder,
        private CrudPageDefinitionProviderInterface $pageDefinitionProvider,
        private CrudSurfaceContractFactory $surfaceContractFactory,
    ) {
    }

    public function provide(CrudRouteContext $routeContext): ?CrudSurfaceContract
    {
        if (!in_array($routeContext->operation, ['index', 'detail', 'show', 'view'], true)) {
            return null;
        }

        $entityClass = $this->entityClassResolver->tryResolve($routeContext->resourcePath);
        if (null === $entityClass) {
            return null;
        }

        $crudContext = new CrudContext(
            surface: 'public',
            operation: in_array($routeContext->operation, ['detail', 'view'], true) ? 'show' : $routeContext->operation,
            resourcePath: $routeContext->resourcePath,
            entityClass: $entityClass,
            identifierField: $routeContext->identifierField(),
            identifierValue: $routeContext->identifierValue(),
            formTypeClass: $this->formTypeResolver->resolve($entityClass),
        );

        if ('index' === $crudContext->operation) {
            $page = $this->pageDefinitionProvider->provideIndex($crudContext);

            return $this->surfaceContractFactory->create($page);
        }

        $object = $this->objectFinder->findOne($crudContext);
        if (null === $object) {
            return null;
        }

        $access = $this->accessContextBuilder->build($crudContext, $object);
        if (!$access->canView) {
            throw new AccessDeniedException('You are not allowed to view this object.');
        }

        $page = $this->pageDefinitionProvider->provideShow($crudContext, $object);

        return $this->surfaceContractFactory->create($page, $object);
    }
}
