<?php

declare(strict_types=1);

namespace App\Cruding\Controller\Surface;

use App\Cruding\Dto\Surface\CrudSurfaceRequest;
use App\Cruding\Service\Crud\CrudNotFoundResponseFactory;
use App\Cruding\Service\CrudRouteShapeResolver;
use App\Cruding\Service\CrudSurfaceGenericFallback;
use App\Cruding\Service\CrudSurfaceProviderLocator;
use App\Cruding\Value\Surface\CrudSurfaceContract;
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
        private readonly CrudSurfaceProviderLocator $providerLocator,
        private readonly CrudSurfaceGenericFallback $genericFallback,
        private readonly CrudNotFoundResponseFactory $notFoundResponseFactory,
    ) {
    }

    public function __invoke(Request $request): Response|CrudSurfaceContract
    {
        $routeContext = $this->routeShapeResolver->resolve($request);
        if (null === $routeContext) {
            return $this->notFoundResponseFactory->create($request, 'crud_route_context_not_found');
        }

        $provider = $this->providerLocator->locate($routeContext);
        if (null !== $provider) {
            return $provider->provide(CrudSurfaceRequest::fromHttpRequest($request, $routeContext));
        }

        $surface = $this->genericFallback->provide($routeContext);
        if (null !== $surface) {
            return $surface;
        }

        return $this->notFoundResponseFactory->create($request, 'crud_surface_provider_not_found');
    }
}
