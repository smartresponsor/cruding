<?php

declare(strict_types=1);

namespace App\Cruding\Controller\Api;

use App\Cruding\DTO\CrudTokenizedRouteIntentDTO;
use App\Cruding\Resolver\CrudActorScopeContextResolver;
use App\Cruding\Service\Api\CrudApiProblemResponseFactory;
use App\Cruding\Service\CrudTokenizedRouteIntentResolver;
use App\Cruding\Service\Runtime\CrudRuntimeRouteGuard;
use App\Cruding\ServiceInterface\Operation\CrudApiCreateOperationInterface;
use App\Cruding\ServiceInterface\Operation\CrudApiDeleteOperationInterface;
use App\Cruding\ServiceInterface\Operation\CrudApiIndexOperationInterface;
use App\Cruding\ServiceInterface\Operation\CrudApiShowOperationInterface;
use App\Cruding\ServiceInterface\Operation\CrudApiUpdateOperationInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;

#[AsController]
/**
 * Handles api controller HTTP requests at the Cruding controller boundary.
 */
final class CrudApiController extends AbstractController
{
    public function __construct(
        private readonly CrudTokenizedRouteIntentResolver $intentResolver,
        private readonly CrudActorScopeContextResolver $actorScopeContextResolver,
        private readonly CrudApiIndexOperationInterface $indexOperation,
        private readonly CrudApiShowOperationInterface $showOperation,
        private readonly CrudApiCreateOperationInterface $createOperation,
        private readonly CrudApiUpdateOperationInterface $updateOperation,
        private readonly CrudApiDeleteOperationInterface $deleteOperation,
        private readonly CrudApiProblemResponseFactory $problemResponseFactory,
        private readonly CrudRuntimeRouteGuard $runtimeRouteGuard,
    ) {
    }

    /**      * Handles the invokable HTTP or application entrypoint.      */
    public function __invoke(Request $request): Response
    {
        $intent = $this->intentResolver->resolveApi($request);
        if (null === $intent || '' === $intent->resourcePath) {
            return $this->problemResponseFactory->notFound('crud_route_intent_not_found', ['path' => $request->getPathInfo()]);
        }

        if (!$this->runtimeRouteGuard->allowsResourcePath($intent->resourcePath)) {
            return $this->problemResponseFactory->notFound('crud_runtime_resource_not_allowed', ['intent' => $intent->diagnostics()]);
        }

        $this->applyIntent($request, $intent);

        return match ($intent->operation) {
            'index' => $this->indexOperation->handle($request),
            'read' => $this->showOperation->handle($request),
            'show' => $this->showOperation->handle($request),
            'create' => $this->createOperation->handle($request),
            'update' => $this->updateOperation->handle($request),
            'delete' => $this->deleteOperation->handle($request),
            default => $this->problemResponseFactory->create(404, 'crud_api_operation_not_supported', [
                'intent' => $intent->diagnostics(),
            ]),
        };
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
        if (null !== $intent->identifierField && null !== $intent->identifierValue) {
            $request->attributes->set($intent->identifierField, $intent->identifierValue);
        }
    }
}
