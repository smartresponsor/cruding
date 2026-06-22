<?php

declare(strict_types=1);

namespace App\Cruding\Service\Crud;

use App\Cruding\Dto\Crud\Entrypoint\CrudEntrypointContext;
use App\Cruding\Dto\Crud\Entrypoint\CrudEntrypointResult;
use App\Cruding\Factory\Crud\CrudNotFoundResponseFactory;
use App\Cruding\Service\Crud\Operation\CrudIdentifierReader;
use App\Cruding\Service\Crud\Surface\CrudSurfaceContractFactory;
use App\Cruding\ServiceInterface\Crud\CrudFormHandlerInterface;
use App\Cruding\ServiceInterface\Crud\CrudPageDefinitionProviderInterface;
use App\Cruding\ServiceInterface\Crud\CrudRouteNameResolverInterface;
use App\Cruding\ServiceInterface\Crud\Entrypoint\CrudEntrypointBehaviorInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final readonly class CrudDefaultServiceBehavior implements CrudEntrypointBehaviorInterface
{
    public function __construct(
        private CrudPageDefinitionProviderInterface $pageDefinitionProvider,
        private CrudSurfaceContractFactory $surfaceContractFactory,
        private CrudFormHandlerInterface $formHandler,
        private CrudRouteNameResolverInterface $routeNameResolver,
        private CrudNotFoundResponseFactory $notFoundResponseFactory,
        private CrudIdentifierReader $identifierReader,
        private UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public function execute(CrudEntrypointContext $context): CrudEntrypointResult
    {
        return match ($context->operation()) {
            'index' => $this->index($context),
            'show' => $this->show($context),
            'new', 'create' => $this->create($context),
            'edit', 'update' => $this->edit($context),
            'delete' => $this->delete($context),
            default => $this->unsupported($context),
        };
    }

    private function index(CrudEntrypointContext $context): CrudEntrypointResult
    {
        return CrudEntrypointResult::surfaceContract(
            $this->surfaceContractFactory->create(
                $this->pageDefinitionProvider->provideIndex($context->crudContext),
            ),
            CrudEntrypointResult::STATUS_DEFAULT_BEHAVIOR,
            $this->diagnostics($context),
        );
    }

    private function show(CrudEntrypointContext $context): CrudEntrypointResult
    {
        if (null === $context->object) {
            return $this->notFound($context, 'crud_resource_not_found');
        }

        return CrudEntrypointResult::surfaceContract(
            $this->surfaceContractFactory->create(
                $this->pageDefinitionProvider->provideShow($context->crudContext, $context->object),
                $context->object,
            ),
            CrudEntrypointResult::STATUS_DEFAULT_BEHAVIOR,
            $this->diagnostics($context),
        );
    }

    private function create(CrudEntrypointContext $context): CrudEntrypointResult
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
            $this->formHandler->persist($context->object);

            $identifierField = $this->identifierReader->detectField($context->object);
            $identifierValue = $this->identifierReader->read($context->object, $identifierField);

            if (null === $identifierValue) {
                return CrudEntrypointResult::response(
                    new RedirectResponse($this->urlGenerator->generate(
                        $this->routeNameResolver->resolveIndex($context->crudContext),
                        $this->routeNameResolver->parameters($context->crudContext, null, null, 'index'),
                    )),
                    CrudEntrypointResult::STATUS_DEFAULT_BEHAVIOR,
                    $this->diagnostics($context),
                );
            }

            return CrudEntrypointResult::response(
                new RedirectResponse($this->urlGenerator->generate(
                    $this->routeNameResolver->resolveShow($context->crudContext, $identifierField),
                    $this->routeNameResolver->parameters(
                        $context->crudContext,
                        $identifierValue,
                        $identifierField,
                        'show',
                    ),
                )),
                CrudEntrypointResult::STATUS_DEFAULT_BEHAVIOR,
                $this->diagnostics($context),
            );
        }

        $formView = $form->createView();

        return CrudEntrypointResult::surfaceContract(
            $this->surfaceContractFactory->create(
                $this->pageDefinitionProvider->provideNew($context->crudContext, $context->object, $formView),
                $context->object,
                $formView,
            ),
            CrudEntrypointResult::STATUS_DEFAULT_BEHAVIOR,
            $this->diagnostics($context),
        );
    }

    private function edit(CrudEntrypointContext $context): CrudEntrypointResult
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

            return CrudEntrypointResult::response(
                new RedirectResponse($this->urlGenerator->generate(
                    $this->routeNameResolver->resolveShow($context->crudContext),
                    $this->routeNameResolver->parameters($context->crudContext, null, null, 'show'),
                )),
                CrudEntrypointResult::STATUS_DEFAULT_BEHAVIOR,
                $this->diagnostics($context),
            );
        }

        $formView = $form->createView();

        return CrudEntrypointResult::surfaceContract(
            $this->surfaceContractFactory->create(
                $this->pageDefinitionProvider->provideEdit($context->crudContext, $context->object, $formView),
                $context->object,
                $formView,
            ),
            CrudEntrypointResult::STATUS_DEFAULT_BEHAVIOR,
            $this->diagnostics($context),
        );
    }

    private function delete(CrudEntrypointContext $context): CrudEntrypointResult
    {
        if (null === $context->object) {
            return $this->notFound($context, 'crud_resource_not_found');
        }

        $this->formHandler->delete($context->object);

        return CrudEntrypointResult::response(
            new RedirectResponse($this->urlGenerator->generate(
                $this->routeNameResolver->resolveIndex($context->crudContext),
                $this->routeNameResolver->parameters($context->crudContext, null, null, 'index'),
            )),
            CrudEntrypointResult::STATUS_DEFAULT_BEHAVIOR,
            $this->diagnostics($context),
        );
    }

    private function unsupported(CrudEntrypointContext $context): CrudEntrypointResult
    {
        return $this->notFound($context, 'crud_operation_not_supported');
    }

    private function notFound(CrudEntrypointContext $context, string $reason): CrudEntrypointResult
    {
        return CrudEntrypointResult::response(
            $this->notFoundResponseFactory->create($context->request, $reason, [
                'resourcePath' => $context->resourcePath(),
                'operation' => $context->operation(),
                'defaultBehavior' => self::class,
            ]),
            CrudEntrypointResult::STATUS_DEFAULT_BEHAVIOR,
            $this->diagnostics($context),
        );
    }

    /** @return array<string, mixed> */
    private function diagnostics(CrudEntrypointContext $context): array
    {
        return [
            'defaultBehavior' => self::class,
            'resourcePath' => $context->resourcePath(),
            'operation' => $context->operation(),
            'entityClass' => $context->crudContext->entityClass,
        ];
    }
}
