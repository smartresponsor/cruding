<?php

declare(strict_types=1);

namespace App\Cruding\Service\Crud\Operation;

use App\Cruding\Dispatcher\Crud\CrudServiceDispatcher;
use App\Cruding\Factory\Crud\CrudNotFoundResponseFactory;
use App\Cruding\Service\Crud\Resource\CrudResourceContractFactory;
use App\Cruding\ServiceInterface\Crud\CrudContextResolverInterface;
use App\Cruding\ServiceInterface\Crud\CrudPageDefinitionProviderInterface;
use App\Cruding\ServiceInterface\Crud\Entrypoint\CrudServiceDispatcherInterface;
use App\Cruding\ServiceInterface\Crud\Operation\CrudIndexOperationInterface;
use App\Cruding\Value\Resource\CrudResourceContract;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final readonly class CrudIndexOperation implements CrudIndexOperationInterface
{
    public function __construct(
        private CrudContextResolverInterface $contextResolver,
        private CrudPageDefinitionProviderInterface $pageDefinitionProvider,
        private CrudResourceContractFactory $viewContractFactory,
        private CrudNotFoundResponseFactory $notFoundResponseFactory,
        #[Autowire(service: CrudServiceDispatcher::class)]
        private CrudServiceDispatcherInterface $entrypointDispatcher,
    ) {
    }

    public function handle(Request $request): Response|CrudResourceContract
    {
        $context = $this->contextResolver->tryResolve($request);
        if (null === $context) {
            return $this->notFoundResponseFactory->create($request, 'crud_context_not_found');
        }

        $entrypointResult = $this->entrypointDispatcher->tryRun($request, $context);
        if (null !== $entrypointResult) {
            return $entrypointResult;
        }

        return $this->viewContractFactory->create($this->pageDefinitionProvider->provideIndex($context));
    }
}
