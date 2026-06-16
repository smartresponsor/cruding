<?php

declare(strict_types=1);

namespace App\Cruding\Service\Crud\Operation;

use App\Cruding\Service\Crud\CrudNotFoundResponseFactory;
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
    ) {
    }

    public function handle(Request $request): Response|CrudSurfaceContract
    {
        $context = $this->contextResolver->tryResolve($request);
        if (null === $context) {
            return $this->notFoundResponseFactory->create($request, 'crud_context_not_found');
        }

        return $this->surfaceContractFactory->create($this->pageDefinitionProvider->provideIndex($context));
    }
}
