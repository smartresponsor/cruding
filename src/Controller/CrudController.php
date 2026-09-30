<?php

declare(strict_types=1);

namespace App\Cruding\Controller;

use App\Cruding\DTO\CrudContextDTO;
use App\Cruding\DTO\CrudTokenizedRouteIntentDTO;
use App\Cruding\Factory\CrudNotFoundResponseFactory;
use App\Cruding\Resolver\CrudActorScopeContextResolver;
use App\Cruding\Resolver\CrudTokenizedRouteIntentResolver;
use App\Cruding\Runner\CrudServiceRunner;
use App\Cruding\Service\Operation\CrudOperationDispatcher;
use App\Cruding\Service\Resource\CrudRouteMapMatcher;
use App\Cruding\Service\Runtime\CrudRuntimeRouteGuard;
use App\Cruding\ServiceInterface\CrudContextResolverInterface;
use App\Cruding\ValueObject\Resource\CrudResourceContract;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;

#[AsController]
/**
 * Handles controller HTTP requests at the Cruding controller boundary.
 */
final class CrudController extends AbstractController
{
    public function __construct(
        private readonly CrudTokenizedRouteIntentResolver $intentResolver,
        private readonly CrudActorScopeContextResolver $actorScopeContextResolver,
        private readonly CrudContextResolverInterface $contextResolver,
        private readonly CrudServiceRunner $entrypointRunner,
        private readonly CrudOperationDispatcher $operationDispatcher,
        private readonly CrudNotFoundResponseFactory $notFoundResponseFactory,
        private readonly CrudRuntimeRouteGuard $runtimeRouteGuard,
        private readonly ?CrudRouteMapMatcher $routeMapMatcher = null,
    ) {
    }

    /**      * Handles the invokable HTTP or application entrypoint.      */
    public function __invoke(Request $request): Response|CrudResourceContract
    {
        $intent = $this->intentResolver->resolveWeb($request);
        if (null === $intent || '' === $intent->resourcePath) {
            return $this->notFoundResponseFactory->create($request, 'crud_route_intent_not_found');
        }

        if (!$this->runtimeRouteGuard->allowsResourcePath($intent->resourcePath)) {
            return $this->notFoundResponseFactory->create($request, 'crud_runtime_resource_not_allowed', ['intent' => $intent->diagnostics()]);
        }

        $this->applyRouteMapEntry($request);
        $this->applyIntent($request, $intent);

        if ($this->operationDispatcher->supports($intent->operation)) {
            return $this->operationDispatcher->handle($intent->operation, $request);
        }

        return $this->runEntrypointOnly($request, $intent);
    }

    private function runEntrypointOnly(Request $request, CrudTokenizedRouteIntentDTO $intent): Response|CrudResourceContract
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
            'interpretation' => 'Tokenized CRUD route matched, but no explicit or URI-derived entrypoint returned a response or view contract.',
        ]);
    }

    private function applyIntent(Request $request, CrudTokenizedRouteIntentDTO $intent): void
    {
        $request->attributes->set('resourcePath', $intent->resourcePath);
        $request->attributes->set('_crud_operation', $intent->operation);
        $request->attributes->set('_crud_view', $intent->view);
        $request->attributes->set('_crud_route_family', $intent->routeFamily);
        $request->attributes->set('_crud_route_tokens', $intent->tokens);
        $this->actorScopeContextResolver->apply($request, $intent);
        $request->attributes->remove('id');
        $request->attributes->remove('slug');

        $identifierValue = $intent->identifierValue;
        if (null !== $intent->identifierField && is_scalar($identifierValue) && '' !== (string) $identifierValue) {
            $request->attributes->set($intent->identifierField, $identifierValue);
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

    private function syntheticContext(CrudTokenizedRouteIntentDTO $intent): CrudContextDTO
    {
        return new CrudContextDTO(
            view: $intent->view,
            operation: $intent->operation,
            resourcePath: $intent->resourcePath,
            entityClass: '',
            identifierField: $intent->identifierField ?? 'slug',
            identifierValue: $intent->identifierValue,
            formTypeClass: null,
        );
    }
}
