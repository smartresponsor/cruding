<?php

declare(strict_types=1);

namespace App\Cruding\Service\Operation\Create;

use App\Cruding\Factory\CrudNotFoundResponseFactory;
use App\Cruding\ServiceInterface\CrudAccessContextBuilderInterface;
use App\Cruding\ServiceInterface\CrudContextResolverInterface;
use App\Cruding\ServiceInterface\CrudObjectFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Provides the create work item initializer responsibility within the Cruding component.
 */
final readonly class CrudCreateWorkItemInitializer
{
    public function __construct(
        private CrudContextResolverInterface $contextResolver,
        private CrudObjectFactoryInterface $objectFactory,
        private CrudAccessContextBuilderInterface $accessContextBuilder,
        private CrudNotFoundResponseFactory $notFoundResponseFactory,
    ) {
    }

    /**      * Executes the initialize operation.      */
    public function initialize(Request $request): CrudCreateWorkItem|Response
    {
        $context = $this->contextResolver->tryResolve($request);
        if (null === $context) {
            return $this->notFoundResponseFactory->create($request, 'crud_context_not_found');
        }

        $entityClass = $context->entityClass;
        if ('' === $entityClass) {
            return $this->notFoundResponseFactory->create($request, 'crud_entity_class_not_found');
        }
        /** @var class-string $entityClass */
        $object = $this->objectFactory->create($entityClass);
        $this->accessContextBuilder->build($context, $object);

        return new CrudCreateWorkItem($context, $object);
    }
}
