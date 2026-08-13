<?php

declare(strict_types=1);

namespace App\Cruding\Service\Crud;

use App\Cruding\Dto\Crud\CrudMutationLifecycleContext;
use App\Cruding\Dto\Crud\Entrypoint\CrudServiceContext;
use App\Cruding\Dto\Crud\Entrypoint\CrudServiceResult;
use App\Cruding\Factory\Crud\CrudNotFoundResponseFactory;
use App\Cruding\Service\Crud\Operation\CrudIdentifierReader;
use App\Cruding\Service\Crud\Resource\CrudResourceContractFactory;
use App\Cruding\ServiceInterface\Crud\CrudFormHandlerInterface;
use App\Cruding\ServiceInterface\Crud\CrudPageDefinitionProviderInterface;
use App\Cruding\ServiceInterface\Crud\CrudRouteNameResolverInterface;
use App\Cruding\ServiceInterface\Crud\Entrypoint\CrudServiceBehaviorInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final readonly class CrudDefaultServiceBehavior implements CrudServiceBehaviorInterface
{
    public function __construct(
        private CrudPageDefinitionProviderInterface $pageDefinitionProvider,
        private CrudResourceContractFactory $viewContractFactory,
        private CrudFormHandlerInterface $formHandler,
        private CrudRouteNameResolverInterface $routeNameResolver,
        private CrudNotFoundResponseFactory $notFoundResponseFactory,
        private CrudIdentifierReader $identifierReader,
        private CrudMutationLifecycleDispatcher $mutationLifecycleDispatcher,
        private UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public function execute(CrudServiceContext $context): CrudServiceResult
    {
        return match ($context->operation()) {
            'index' => $this->index($context),
            'show' => $this->show($context),
            'page' => $this->page($context),
            'new', 'create' => $this->create($context),
            'edit', 'update' => $this->edit($context),
            'delete' => $this->delete($context),
            default => $this->unsupported($context),
        };
    }

    private function index(CrudServiceContext $context): CrudServiceResult
    {
        $definitionStartedAt = hrtime(true);
        $definition = $this->pageDefinitionProvider->provideIndex($context->crudContext);
        $context->request->attributes->set('_crud_default_index_definition_ms', number_format((hrtime(true) - $definitionStartedAt) / 1_000_000, 2, '.', ''));

        $contractStartedAt = hrtime(true);
        $contract = $this->viewContractFactory->create($definition);
        $context->request->attributes->set('_crud_default_index_contract_ms', number_format((hrtime(true) - $contractStartedAt) / 1_000_000, 2, '.', ''));

        return CrudServiceResult::viewContract(
            $contract,
            CrudServiceResult::STATUS_DEFAULT_BEHAVIOR,
            $this->diagnostics($context),
        );
    }

    private function show(CrudServiceContext $context): CrudServiceResult
    {
        if (null === $context->object) {
            return $this->notFound($context, 'crud_resource_not_found');
        }

        return CrudServiceResult::viewContract(
            $this->viewContractFactory->create(
                $this->pageDefinitionProvider->provideShow($context->crudContext, $context->object),
                $context->object,
            ),
            CrudServiceResult::STATUS_DEFAULT_BEHAVIOR,
            $this->diagnostics($context),
        );
    }

    private function page(CrudServiceContext $context): CrudServiceResult
    {
        if (null === $context->object) {
            return $this->notFound($context, 'crud_resource_not_found');
        }

        return CrudServiceResult::viewContract(
            $this->viewContractFactory->create(
                $this->pageDefinitionProvider->providePage($context->crudContext, $context->object),
                $context->object,
            ),
            CrudServiceResult::STATUS_DEFAULT_BEHAVIOR,
            $this->diagnostics($context),
        );
    }

    private function create(CrudServiceContext $context): CrudServiceResult
    {
        if (null === $context->object || null === $context->crudContext->formTypeClass) {
            return $this->notFound($context, 'crud_resource_not_found');
        }

        $form = $this->formHandler->createAndHandle(
            $context->crudContext->formTypeClass,
            $context->object,
            $context->request,
        );

        if ($form->isSubmitted() && $form->isValid()) {
            $lifecycleContext = new CrudMutationLifecycleContext(
                $context->crudContext,
                $context->object,
                $context->request,
                'create',
            );
            $this->mutationLifecycleDispatcher->execute(
                $lifecycleContext,
                fn (): mixed => $this->formHandler->persist($context->object),
            );

            $identifierField = $this->identifierReader->detectField($context->object);
            $identifierValue = $this->identifierReader->read($context->object, $identifierField);

            if (null === $identifierValue) {
                return CrudServiceResult::response(
                    new RedirectResponse($this->urlGenerator->generate(
                        $this->routeNameResolver->resolveIndex($context->crudContext),
                        $this->routeNameResolver->parameters($context->crudContext, null, null, 'index'),
                    )),
                    CrudServiceResult::STATUS_DEFAULT_BEHAVIOR,
                    $this->diagnostics($context),
                );
            }

            return CrudServiceResult::response(
                new RedirectResponse($this->urlGenerator->generate(
                    $this->routeNameResolver->resolveShow($context->crudContext, $identifierField),
                    $this->routeNameResolver->parameters(
                        $context->crudContext,
                        $identifierValue,
                        $identifierField,
                        'show',
                    ),
                )),
                CrudServiceResult::STATUS_DEFAULT_BEHAVIOR,
                $this->diagnostics($context),
            );
        }

        $formView = $form->createView();

        return CrudServiceResult::viewContract(
            $this->viewContractFactory->create(
                $this->pageDefinitionProvider->provideNew($context->crudContext, $context->object, $formView),
                $context->object,
                $formView,
            ),
            CrudServiceResult::STATUS_DEFAULT_BEHAVIOR,
            $this->diagnostics($context),
        );
    }

    private function edit(CrudServiceContext $context): CrudServiceResult
    {
        if (null === $context->object || null === $context->crudContext->formTypeClass) {
            return $this->notFound($context, 'crud_resource_not_found');
        }

        $form = $this->formHandler->createAndHandle(
            $context->crudContext->formTypeClass,
            $context->object,
            $context->request,
        );

        if ($form->isSubmitted() && $form->isValid()) {
            $this->formHandler->flush($context->object);

            return CrudServiceResult::response(
                new RedirectResponse($this->urlGenerator->generate(
                    $this->routeNameResolver->resolveShow($context->crudContext),
                    $this->routeNameResolver->parameters($context->crudContext, null, null, 'show'),
                )),
                CrudServiceResult::STATUS_DEFAULT_BEHAVIOR,
                $this->diagnostics($context),
            );
        }

        $formView = $form->createView();

        return CrudServiceResult::viewContract(
            $this->viewContractFactory->create(
                $this->pageDefinitionProvider->provideEdit($context->crudContext, $context->object, $formView),
                $context->object,
                $formView,
            ),
            CrudServiceResult::STATUS_DEFAULT_BEHAVIOR,
            $this->diagnostics($context),
        );
    }

    private function delete(CrudServiceContext $context): CrudServiceResult
    {
        if (null === $context->object) {
            return $this->notFound($context, 'crud_resource_not_found');
        }

        $this->formHandler->delete($context->object);

        return CrudServiceResult::response(
            new RedirectResponse($this->urlGenerator->generate(
                $this->routeNameResolver->resolveIndex($context->crudContext),
                $this->routeNameResolver->parameters($context->crudContext, null, null, 'index'),
            )),
            CrudServiceResult::STATUS_DEFAULT_BEHAVIOR,
            $this->diagnostics($context),
        );
    }

    private function unsupported(CrudServiceContext $context): CrudServiceResult
    {
        return $this->notFound($context, 'crud_operation_not_supported');
    }

    private function notFound(CrudServiceContext $context, string $reason): CrudServiceResult
    {
        return CrudServiceResult::response(
            $this->notFoundResponseFactory->create($context->request, $reason, [
                'resourcePath' => $context->resourcePath(),
                'operation' => $context->operation(),
                'defaultBehavior' => self::class,
            ]),
            CrudServiceResult::STATUS_DEFAULT_BEHAVIOR,
            $this->diagnostics($context),
        );
    }

    /** @return array<string, mixed> */
    private function diagnostics(CrudServiceContext $context): array
    {
        return [
            'defaultBehavior' => self::class,
            'resourcePath' => $context->resourcePath(),
            'operation' => $context->operation(),
            'entityClass' => $context->crudContext->entityClass,
        ];
    }
}
