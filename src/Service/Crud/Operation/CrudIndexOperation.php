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
        $contextStartedAt = hrtime(true);
        $context = $this->contextResolver->tryResolve($request);
        $request->attributes->set('_crud_context_ms', number_format((hrtime(true) - $contextStartedAt) / 1_000_000, 2, '.', ''));
        if (null === $context) {
            return $this->notFoundResponseFactory->create($request, 'crud_context_not_found');
        }

        $entrypointStartedAt = hrtime(true);
        $entrypointResult = $this->entrypointDispatcher->tryRun($request, $context);
        $request->attributes->set('_crud_entrypoint_ms', number_format((hrtime(true) - $entrypointStartedAt) / 1_000_000, 2, '.', ''));
        if (null !== $entrypointResult) {
            return $entrypointResult;
        }

        $definitionStartedAt = hrtime(true);
        $definition = $this->pageDefinitionProvider->provideIndex($context);
        $request->attributes->set('_crud_definition_ms', number_format((hrtime(true) - $definitionStartedAt) / 1_000_000, 2, '.', ''));

        $contractStartedAt = hrtime(true);
        $contract = $this->viewContractFactory->create($definition);
        $request->attributes->set('_crud_contract_factory_ms', number_format((hrtime(true) - $contractStartedAt) / 1_000_000, 2, '.', ''));

        return $contract;
    }
}
