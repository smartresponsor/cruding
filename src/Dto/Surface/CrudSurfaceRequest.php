<?php

declare(strict_types=1);

namespace App\Cruding\Dto\Surface;

use Symfony\Component\HttpFoundation\Request;

/**
 * Normalized request handed to producer surface providers.
 */
final readonly class CrudSurfaceRequest
{
    /**
     * @param array<string, mixed> $query
     * @param array<string, mixed> $attributes
     */
    public function __construct(
        public CrudRouteContext $routeContext,
        public string $locale,
        public string $method,
        public array $query,
        public array $attributes,
        public ?Request $httpRequest = null,
    ) {
    }

    public static function fromHttpRequest(Request $request, CrudRouteContext $routeContext): self
    {
        return new self(
            routeContext: $routeContext,
            locale: $request->getLocale(),
            method: $request->getMethod(),
            query: $request->query->all(),
            attributes: $request->attributes->all(),
            httpRequest: $request,
        );
    }
}
