<?php

declare(strict_types=1);

namespace App\Cruding\Controller\Crud;

use App\Cruding\Dto\Resource\CrudResourceRequest;
use App\Cruding\Factory\Crud\CrudNotFoundResponseFactory;
use App\Cruding\Service\Crud\Resource\CrudResourceGenericFallback;
use App\Cruding\Service\Crud\Resource\CrudResourceProviderLocator;
use App\Cruding\Service\Crud\Resource\CrudResourceServiceInvoker;
use App\Cruding\Service\Crud\Resource\CrudResourceServiceResolver;
use App\Cruding\Service\Crud\Resource\CrudRouteShapeResolver;
use App\Cruding\Service\Crud\Runtime\CrudRuntimeRouteGuard;
use App\Cruding\Value\Resource\CrudResourceContract;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Universal read-oriented controller for resource-bound routes.
 */
final class CrudResourceController extends AbstractController
{
    public function __construct(
        private readonly CrudRouteShapeResolver $routeShapeResolver,
        private readonly CrudResourceServiceResolver $serviceResolver,
        private readonly CrudResourceServiceInvoker $serviceInvoker,
        private readonly CrudResourceProviderLocator $providerLocator,
        private readonly CrudResourceGenericFallback $genericFallback,
        private readonly CrudNotFoundResponseFactory $notFoundResponseFactory,
        private readonly CrudRuntimeRouteGuard $runtimeRouteGuard,
        private readonly bool $debug = false,
        private readonly ?LoggerInterface $logger = null,
    ) {
    }

    public function __invoke(Request $request): Response|CrudResourceContract
    {
        $routeContext = $this->routeShapeResolver->resolve($request);
        if (null === $routeContext) {
            return $this->notFoundResponseFactory->create($request, 'crud_route_context_not_found');
        }

        if (!$this->runtimeRouteGuard->allowsResourcePath($routeContext->resourcePath)) {
            return $this->notFoundResponseFactory->create($request, 'crud_runtime_resource_not_allowed', ['routeContext' => $routeContext->toArray()]);
        }

        $viewRequest = CrudResourceRequest::fromHttpRequest($request, $routeContext);

        $service = $this->serviceResolver->resolve($routeContext);
        if (null !== $service) {
            try {
                return $this->serviceInvoker->invoke($service, $viewRequest);
            } catch (\Throwable $exception) {
                $this->logger?->error('Cruding resource service invocation failed.', [
                    'exception' => $exception,
                    'path' => $request->getPathInfo(),
                    'routeContext' => $routeContext->toArray(),
                    'serviceDiagnostics' => $this->serviceResolver->lastDiagnostics(),
                ]);

                return $this->notFoundResponseFactory->create(
                    $request,
                    'crud_resource_service_invalid',
                    $this->debug ? [
                        'path' => $request->getPathInfo(),
                        'routeContext' => $routeContext->toArray(),
                        'serviceDiagnostics' => $this->serviceResolver->lastDiagnostics(),
                        'error' => $exception->getMessage(),
                    ] : []
                );
            }
        }

        $provider = $this->providerLocator->locate($routeContext);
        if (null !== $provider) {
            return $provider->provide($viewRequest);
        }

        $view = $this->genericFallback->provide($routeContext);
        if (null !== $view) {
            return $view;
        }

        $metadata = [
            'path' => $request->getPathInfo(),
            'matchedRoute' => $request->attributes->get('_route'),
            'routeFamily' => str_starts_with((string) $request->attributes->get('_route', ''), 'cruding_resource_') ? 'resource' : 'unknown',
            'routeParameters' => $this->routeParameters($request),
            'meaning' => 'Resource grammar was recognized, but no route-map service, FQCN service, provider, or generic fallback could serve it.',
            'routeContext' => $routeContext->toArray(),
            'serviceDiagnostics' => $this->serviceResolver->lastDiagnostics(),
            'providerKeys' => $routeContext->providerKeys,
            'registeredProviderKeys' => $this->providerLocator->keys(),
            'templateCandidates' => $routeContext->templateCandidates,
        ];

        $this->logger?->warning('Cruding resource not found.', [
            'path' => $request->getPathInfo(),
            'routeContext' => $routeContext->toArray(),
            'serviceDiagnostics' => $this->serviceResolver->lastDiagnostics(),
            'providerKeys' => $routeContext->providerKeys,
        ]);

        return $this->notFoundResponseFactory->create(
            $request,
            'crud_resource_artifact_not_found',
            $this->debug ? $metadata : []
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function routeParameters(Request $request): array
    {
        $parameters = [];
        foreach ($request->attributes->all() as $key => $value) {
            if (str_starts_with((string) $key, '_')) {
                continue;
            }

            if (is_scalar($value) || null === $value) {
                $parameters[(string) $key] = $value;
            }
        }

        return $parameters;
    }
}
