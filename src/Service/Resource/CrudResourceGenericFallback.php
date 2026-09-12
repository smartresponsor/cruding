<?php

declare(strict_types=1);

namespace App\Cruding\Service\Resource;

use App\Cruding\DTO\CrudContextDTO;
use App\Cruding\DTO\Resource\CrudRouteContextDTO;
use App\Cruding\Factory\Resource\CrudResourceContractFactory;
use App\Cruding\Resolver\CrudEntityClassResolver;
use App\Cruding\Resolver\CrudFormTypeResolver;
use App\Cruding\ServiceInterface\CrudAccessContextBuilderInterface;
use App\Cruding\ServiceInterface\CrudObjectFinderInterface;
use App\Cruding\ServiceInterface\CrudPageDefinitionProviderInterface;
use App\Cruding\ValueObject\Resource\CrudResourceContract;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * Provides the resource generic fallback responsibility within the Cruding component.
 */
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

    /**      * Executes the provide operation.      */
    public function provide(CrudRouteContextDTO $routeContext): ?CrudResourceContract
    {
        if (!in_array($routeContext->operation, ['index', 'detail', 'show', 'view'], true)) {
            return null;
        }

        $entityClass = $this->entityClassResolver->tryResolve($routeContext->resourcePath);
        if (null === $entityClass) {
            return null;
        }

        $crudContext = new CrudContextDTO(
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
