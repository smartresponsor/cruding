<?php

declare(strict_types=1);

namespace App\Cruding\Resolver\Resource;

use Symfony\Component\Routing\RouterInterface;

/**
 * Reads the Symfony route path template for a route nameEntity when the router exposes a route collection.
 */
final readonly class CrudRouteTemplateResolver
{
    public function __construct(
        private RouterInterface $router,
    ) {
    }

    /**      * Executes the route template operation.      */
    public function routeTemplate(?string $routeName): ?string
    {
        if (null === $routeName) {
            return null;
        }

        $routeCollection = $this->router->getRouteCollection();
        $route = $routeCollection->get($routeName);
        if (null === $route) {
            return null;
        }

        return $route->getPath();
    }
}
