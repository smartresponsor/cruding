<?php

declare(strict_types=1);

namespace App\Cruding\Controller\Surface;

use App\Cruding\Dto\Surface\CrudSurfaceRequest;
use App\Cruding\Service\Crud\CrudNotFoundResponseFactory;
use App\Cruding\Service\Crud\Surface\CrudRouteShapeResolver;
use App\Cruding\Service\Crud\Surface\CrudSurfaceGenericFallback;
use App\Cruding\Service\Crud\Surface\CrudSurfaceProviderLocator;
use App\Cruding\Service\Crud\Surface\CrudSurfaceServiceInvoker;
use App\Cruding\Service\Crud\Surface\CrudSurfaceServiceResolver;
use App\Cruding\Value\Surface\CrudSurfaceContract;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Universal read-oriented controller for resource-bound surface routes.
 */
final class CrudSurfaceController extends AbstractController
{
    public function __construct(
        private readonly CrudRouteShapeResolver $routeShapeResolver,
        private readonly CrudSurfaceServiceResolver $serviceResolver,
        private readonly CrudSurfaceServiceInvoker $serviceInvoker,
        private readonly CrudSurfaceProviderLocator $providerLocator,
        private readonly CrudSurfaceGenericFallback $genericFallback,
        private readonly CrudNotFoundResponseFactory $notFoundResponseFactory,
        private readonly bool $debug = false,
        private readonly ?LoggerInterface $logger = null,
    ) {
    }

    public function __invoke(Request $request): Response|CrudSurfaceContract
    {
        $routeContext = $this->routeShapeResolver->resolve($request);
        if (null === $routeContext) {
            return $this->notFoundResponseFactory->create($request, 'crud_route_context_not_found');
        }

        $surfaceRequest = CrudSurfaceRequest::fromHttpRequest($request, $routeContext);

        $service = $this->serviceResolver->resolve($routeContext);
        if (null !== $service) {
            try {
                return $this->serviceInvoker->invoke($service, $surfaceRequest);
            } catch (\Throwable $exception) {
                $this->logger?->error('Cruding surface service invocation failed.', [
                    'exception' => $exception,
                    'path' => $request->getPathInfo(),
                    'routeContext' => $routeContext->toArray(),
                    'serviceDiagnostics' => $this->serviceResolver->lastDiagnostics(),
                ]);

                return $this->notFoundResponseFactory->create(
                    $request,
                    'crud_surface_service_invalid',
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
            return $provider->provide($surfaceRequest);
        }

        $surface = $this->genericFallback->provide($routeContext);
        if (null !== $surface) {
            return $surface;
        }

        $metadata = [
            'path' => $request->getPathInfo(),
            'matchedRoute' => $request->attributes->get('_route'),
            'routeFamily' => str_starts_with((string) $request->attributes->get('_route', ''), 'cruding_surface_') ? 'surface' : 'unknown',
            'routeParameters' => $this->routeParameters($request),
            'meaning' => 'Surface grammar was recognized, but no route-map service, FQCN service, provider, or generic fallback could serve it.',
            'routeContext' => $routeContext->toArray(),
            'serviceDiagnostics' => $this->serviceResolver->lastDiagnostics(),
            'providerKeys' => $routeContext->providerKeys,
            'registeredProviderKeys' => $this->providerLocator->keys(),
            'templateCandidates' => $routeContext->templateCandidates,
        ];

        $this->logger?->warning('Cruding surface not found.', [
            'path' => $request->getPathInfo(),
            'routeContext' => $routeContext->toArray(),
            'serviceDiagnostics' => $this->serviceResolver->lastDiagnostics(),
            'providerKeys' => $routeContext->providerKeys,
        ]);

        return $this->notFoundResponseFactory->create(
            $request,
            'crud_surface_artifact_not_found',
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
