<?php

declare(strict_types=1);

namespace App\Cruding\Service;

use App\Cruding\DTO\CrudMutationLifecycleContextDTO;
use App\Cruding\DTO\Entrypoint\CrudServiceContextDTO;
use App\Cruding\DTO\Entrypoint\CrudServiceResultDTO;
use App\Cruding\Factory\CrudNotFoundResponseFactory;
use App\Cruding\Service\Operation\CrudIdentifierReader;
use App\Cruding\Service\Resource\CrudResourceContractFactory;
use App\Cruding\ServiceInterface\CrudFormHandlerInterface;
use App\Cruding\ServiceInterface\CrudPageDefinitionProviderInterface;
use App\Cruding\ServiceInterface\CrudRouteNameResolverInterface;
use App\Cruding\ServiceInterface\Entrypoint\CrudServiceBehaviorInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Provides the default service behavior responsibility within the Cruding component.
 */
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

    /**      * Executes the operation represented by this service.      */
    public function execute(CrudServiceContextDTO $context): CrudServiceResultDTO
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

    private function index(CrudServiceContextDTO $context): CrudServiceResultDTO
    {
        $definitionStartedAt = hrtime(true);
        $definition = $this->pageDefinitionProvider->provideIndex($context->crudContext);
        $context->request->attributes->set('_crud_default_index_definition_ms', number_format((hrtime(true) - $definitionStartedAt) / 1_000_000, 2, '.', ''));

        $contractStartedAt = hrtime(true);
        $contract = $this->viewContractFactory->create($definition);
        $context->request->attributes->set('_crud_default_index_contract_ms', number_format((hrtime(true) - $contractStartedAt) / 1_000_000, 2, '.', ''));

        return CrudServiceResultDTO::viewContract(
            $contract,
            CrudServiceResultDTO::STATUS_DEFAULT_BEHAVIOR,
            $this->diagnostics($context),
        );
    }

    private function show(CrudServiceContextDTO $context): CrudServiceResultDTO
    {
        if (null === $context->object) {
            return $this->notFound($context, 'crud_resource_not_found');
        }

        return CrudServiceResultDTO::viewContract(
            $this->viewContractFactory->create(
                $this->pageDefinitionProvider->provideShow($context->crudContext, $context->object),
                $context->object,
            ),
            CrudServiceResultDTO::STATUS_DEFAULT_BEHAVIOR,
            $this->diagnostics($context),
        );
    }

    private function page(CrudServiceContextDTO $context): CrudServiceResultDTO
    {
        if (null === $context->object) {
            return $this->notFound($context, 'crud_resource_not_found');
        }

        return CrudServiceResultDTO::viewContract(
            $this->viewContractFactory->create(
                $this->pageDefinitionProvider->providePage($context->crudContext, $context->object),
                $context->object,
            ),
            CrudServiceResultDTO::STATUS_DEFAULT_BEHAVIOR,
            $this->diagnostics($context),
        );
    }

    private function create(CrudServiceContextDTO $context): CrudServiceResultDTO
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
            $lifecycleContext = new CrudMutationLifecycleContextDTO(
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
                return CrudServiceResultDTO::response(
                    new RedirectResponse($this->urlGenerator->generate(
                        $this->routeNameResolver->resolveIndex($context->crudContext),
                        $this->routeNameResolver->parameters($context->crudContext, null, null, 'index'),
                    )),
                    CrudServiceResultDTO::STATUS_DEFAULT_BEHAVIOR,
                    $this->diagnostics($context),
                );
            }

            return CrudServiceResultDTO::response(
                new RedirectResponse($this->urlGenerator->generate(
                    $this->routeNameResolver->resolveShow($context->crudContext, $identifierField),
                    $this->routeNameResolver->parameters(
                        $context->crudContext,
                        $identifierValue,
                        $identifierField,
                        'show',
                    ),
                )),
                CrudServiceResultDTO::STATUS_DEFAULT_BEHAVIOR,
                $this->diagnostics($context),
            );
        }

        $formView = $form->createView();

        return CrudServiceResultDTO::viewContract(
            $this->viewContractFactory->create(
                $this->pageDefinitionProvider->provideNew($context->crudContext, $context->object, $formView),
                $context->object,
                $formView,
            ),
            CrudServiceResultDTO::STATUS_DEFAULT_BEHAVIOR,
            $this->diagnostics($context),
        );
    }

    private function edit(CrudServiceContextDTO $context): CrudServiceResultDTO
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

            return CrudServiceResultDTO::response(
                new RedirectResponse($this->urlGenerator->generate(
                    $this->routeNameResolver->resolveShow($context->crudContext),
                    $this->routeNameResolver->parameters($context->crudContext, null, null, 'show'),
                )),
                CrudServiceResultDTO::STATUS_DEFAULT_BEHAVIOR,
                $this->diagnostics($context),
            );
        }

        $formView = $form->createView();

        return CrudServiceResultDTO::viewContract(
            $this->viewContractFactory->create(
                $this->pageDefinitionProvider->provideEdit($context->crudContext, $context->object, $formView),
                $context->object,
                $formView,
            ),
            CrudServiceResultDTO::STATUS_DEFAULT_BEHAVIOR,
            $this->diagnostics($context),
        );
    }

    private function delete(CrudServiceContextDTO $context): CrudServiceResultDTO
    {
        if (null === $context->object) {
            return $this->notFound($context, 'crud_resource_not_found');
        }

        $this->formHandler->delete($context->object);

        return CrudServiceResultDTO::response(
            new RedirectResponse($this->urlGenerator->generate(
                $this->routeNameResolver->resolveIndex($context->crudContext),
                $this->routeNameResolver->parameters($context->crudContext, null, null, 'index'),
            )),
            CrudServiceResultDTO::STATUS_DEFAULT_BEHAVIOR,
            $this->diagnostics($context),
        );
    }

    private function unsupported(CrudServiceContextDTO $context): CrudServiceResultDTO
    {
        return $this->notFound($context, 'crud_operation_not_supported');
    }

    private function notFound(CrudServiceContextDTO $context, string $reason): CrudServiceResultDTO
    {
        return CrudServiceResultDTO::response(
            $this->notFoundResponseFactory->create($context->request, $reason, [
                'resourcePath' => $context->resourcePath(),
                'operation' => $context->operation(),
                'defaultBehavior' => self::class,
            ]),
            CrudServiceResultDTO::STATUS_DEFAULT_BEHAVIOR,
            $this->diagnostics($context),
        );
    }

    /** @return array<string, mixed> */
    private function diagnostics(CrudServiceContextDTO $context): array
    {
        return [
            'defaultBehavior' => self::class,
            'resourcePath' => $context->resourcePath(),
            'operation' => $context->operation(),
            'entityClass' => $context->crudContext->entityClass,
        ];
    }
}
