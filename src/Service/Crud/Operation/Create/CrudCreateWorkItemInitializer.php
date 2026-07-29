<?php

declare(strict_types=1);

namespace App\Cruding\Service\Crud\Operation\Create;

use App\Cruding\Factory\Crud\CrudNotFoundResponseFactory;
use App\Cruding\ServiceInterface\Crud\CrudAccessContextBuilderInterface;
use App\Cruding\ServiceInterface\Crud\CrudContextResolverInterface;
use App\Cruding\ServiceInterface\Crud\CrudObjectFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final readonly class CrudCreateWorkItemInitializer
{
    public function __construct(
        private CrudContextResolverInterface $contextResolver,
        private CrudObjectFactoryInterface $objectFactory,
        private CrudAccessContextBuilderInterface $accessContextBuilder,
        private CrudNotFoundResponseFactory $notFoundResponseFactory,
    ) {
    }

    public function initialize(Request $request): CrudCreateWorkItem|Response
    {
        $context = $this->contextResolver->tryResolve($request);
        if (null === $context) {
            return $this->notFoundResponseFactory->create($request, 'crud_context_not_found');
        }

        $object = $this->objectFactory->create($context->entityClass);
        $this->accessContextBuilder->build($context, $object);

        return new CrudCreateWorkItem($context, $object);
    }
}
