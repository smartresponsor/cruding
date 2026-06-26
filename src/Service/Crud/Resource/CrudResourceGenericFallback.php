<?php

declare(strict_types=1);

namespace App\Cruding\Service\Crud\Resource;

use App\Cruding\Dto\Crud\CrudContext;
use App\Cruding\Dto\Resource\CrudRouteContext;
use App\Cruding\Resolver\Crud\CrudEntityClassResolver;
use App\Cruding\Resolver\Crud\CrudFormTypeResolver;
use App\Cruding\ServiceInterface\Crud\CrudAccessContextBuilderInterface;
use App\Cruding\ServiceInterface\Crud\CrudObjectFinderInterface;
use App\Cruding\ServiceInterface\Crud\CrudPageDefinitionProviderInterface;
use App\Cruding\Value\Resource\CrudResourceContract;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

final readonly class CrudResourceGenericFallback
{
    public function __construct(
        private CrudEntityClassResolver $entityClassResolver,
        private CrudFormTypeResolver $formTypeResolver,
        private CrudObjectFinderInterface $objectFinder,
        private CrudAccessContextBuilderInterface $accessContextBuilder,
        private CrudPageDefinitionProviderInterface $pageDefinitionProvider,
        private CrudResourceContractFactory $viewContractFactory,
    ) {
    }

    public function provide(CrudRouteContext $routeContext): ?CrudResourceContract
    {
        if (!in_array($routeContext->operation, ['index', 'detail', 'show', 'view'], true)) {
            return null;
        }

        $entityClass = $this->entityClassResolver->tryResolve($routeContext->resourcePath);
        if (null === $entityClass) {
            return null;
        }

        $crudContext = new CrudContext(
            view: 'public',
            operation: in_array($routeContext->operation, ['detail', 'view'], true) ? 'show' : $routeContext->operation,
            resourcePath: $routeContext->resourcePath,
            entityClass: $entityClass,
            identifierField: $routeContext->identifierField(),
            identifierValue: $routeContext->identifierValue(),
            formTypeClass: $this->formTypeResolver->resolve($entityClass),
        );

        if ('index' === $crudContext->operation) {
            return $this->viewContractFactory->create($this->pageDefinitionProvider->provideIndex($crudContext));
        }

        $object = $this->objectFinder->findOne($crudContext);
        if (null === $object) {
            return null;
        }

        $access = $this->accessContextBuilder->build($crudContext, $object);
        if (!$access->canView) {
            throw new AccessDeniedException('You are not allowed to view this object.');
        }

        return $this->viewContractFactory->create($this->pageDefinitionProvider->provideShow($crudContext, $object), $object);
    }
}
