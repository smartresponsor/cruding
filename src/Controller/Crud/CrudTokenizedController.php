<?php

declare(strict_types=1);

namespace App\Cruding\Controller\Crud;

use App\Cruding\Dto\Crud\CrudContext;
use App\Cruding\Dto\Crud\CrudTokenizedRouteIntent;
use App\Cruding\Service\Crud\CrudActorScopeContextResolver;
use App\Cruding\Service\Crud\CrudNotFoundResponseFactory;
use App\Cruding\Service\Crud\CrudTokenizedRouteIntentResolver;
use App\Cruding\Service\Crud\Entrypoint\CrudEntrypointOperationRunner;
use App\Cruding\Service\Crud\Surface\CrudRouteMapMatcher;
use App\Cruding\ServiceInterface\Crud\CrudContextResolverInterface;
use App\Cruding\ServiceInterface\Crud\Operation\CrudCreateOperationInterface;
use App\Cruding\ServiceInterface\Crud\Operation\CrudDeleteOperationInterface;
use App\Cruding\ServiceInterface\Crud\Operation\CrudEditOperationInterface;
use App\Cruding\ServiceInterface\Crud\Operation\CrudIndexOperationInterface;
use App\Cruding\ServiceInterface\Crud\Operation\CrudShowOperationInterface;
use App\Cruding\Value\Surface\CrudSurfaceContract;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;

#[AsController]
final class CrudTokenizedController extends AbstractController
{
    private const DEFAULT_OPERATION_HANDLER = [
        'index' => 'index', 'show' => 'show', 'new' => 'create', 'create' => 'create',
        'import' => 'create', 'bulk' => 'create', 'edit' => 'edit', 'update' => 'edit',
        'archive' => 'edit', 'restore' => 'edit', 'duplicate' => 'edit', 'delete' => 'delete',
    ];

    public function __construct(
        private readonly CrudTokenizedRouteIntentResolver $intentResolver,
        private readonly CrudActorScopeContextResolver $actorScopeContextResolver,
        private readonly CrudContextResolverInterface $contextResolver,
        private readonly CrudEntrypointOperationRunner $entrypointRunner,
        private readonly CrudIndexOperationInterface $indexOperation,
        private readonly CrudShowOperationInterface $showOperation,
        private readonly CrudCreateOperationInterface $createOperation,
        private readonly CrudEditOperationInterface $editOperation,
        private readonly CrudDeleteOperationInterface $deleteOperation,
        private readonly CrudNotFoundResponseFactory $notFoundResponseFactory,
        private readonly ?CrudRouteMapMatcher $routeMapMatcher = null,
    ) {
    }

    public function __invoke(Request $request): Response|CrudSurfaceContract
    {
        $intent = $this->intentResolver->resolveWeb($request);
        if (null === $intent || '' === $intent->resourcePath) {
            return $this->notFoundResponseFactory->create($request, 'crud_route_intent_not_found');
        }

        $this->applyRouteMapEntry($request);
        $this->applyIntent($request, $intent);
        $handler = self::DEFAULT_OPERATION_HANDLER[$intent->operation] ?? null;

        if (null !== $handler) {
            return match ($handler) {
                'index' => $this->indexOperation->handle($request),
                'show' => $this->showOperation->handle($request),
                'create' => $this->createOperation->handle($request),
                'edit' => $this->editOperation->handle($request),
                'delete' => $this->deleteOperation->handle($request),
                default => $this->runEntrypointOnly($request, $intent),
            };
        }

        return $this->runEntrypointOnly($request, $intent);
    }

    private function runEntrypointOnly(Request $request, CrudTokenizedRouteIntent $intent): Response|CrudSurfaceContract
    {
        $context = $this->contextResolver->tryResolve($request) ?? $this->syntheticContext($intent);
        $result = $this->entrypointRunner->run($request, $context);
        $payload = $result->payload();
        if (null !== $payload) {
            return $payload;
        }

        return $this->notFoundResponseFactory->create($request, 'crud_entrypoint_not_found', [
            'intent' => $intent->diagnostics(),
            'entrypointTrace' => $result->diagnostics()['entrypointTrace'] ?? $result->diagnostics(),
            'interpretation' => 'Tokenized CRUD route matched, but no explicit or URI-derived entrypoint returned a response or surface contract.',
        ]);
    }

    private function applyIntent(Request $request, CrudTokenizedRouteIntent $intent): void
    {
        $request->attributes->set('resourcePath', $intent->resourcePath);
        $request->attributes->set('_crud_operation', $intent->operation);
        $request->attributes->set('_crud_surface', $intent->surface);
        $request->attributes->set('_crud_route_family', $intent->routeFamily);
        $request->attributes->set('_crud_route_tokens', $intent->tokens);
        $this->actorScopeContextResolver->apply($request, $intent);
        $request->attributes->remove('id');
        $request->attributes->remove('slug');
        if (null !== $intent->identifierField && null !== $intent->identifierValue) {
            $request->attributes->set($intent->identifierField, $intent->identifierValue);
        }
    }

    private function applyRouteMapEntry(Request $request): void
    {
        if (null === $this->routeMapMatcher) {
            return;
        }

        $routeMapEntry = $this->routeMapMatcher->match($request);
        if (null === $routeMapEntry) {
            return;
        }

        $request->attributes->set('_crud_route_key', $routeMapEntry->canonicalKey());
        if (null !== $routeMapEntry->service && '' !== $routeMapEntry->service) {
            foreach (['_crud_entrypoint_service', '_crud_service', '_crud_handler_service', 'crud_service'] as $attribute) {
                $request->attributes->set($attribute, $routeMapEntry->service);
            }
        }
    }

    private function syntheticContext(CrudTokenizedRouteIntent $intent): CrudContext
    {
        return new CrudContext(
            surface: $intent->surface,
            operation: $intent->operation,
            resourcePath: $intent->resourcePath,
            entityClass: '',
            identifierField: $intent->identifierField ?? 'slug',
            identifierValue: $intent->identifierValue,
            formTypeClass: null,
        );
    }
}
