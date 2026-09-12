<?php

declare(strict_types=1);

namespace App\Cruding\DTO\Resource;

use Symfony\Component\HttpFoundation\Request;

/**
 * Normalized request handed to producer resource providers.
 */
final readonly class CrudResourceRequestDTO
{
    /**
     * @param array<string, mixed> $query
     * @param array<string, mixed> $attributes
     */
    public function __construct(
        public CrudRouteContextDTO $routeContext,
        public string $locale,
        public string $method,
        public array $query,
        public array $attributes,
        public ?Request $httpRequest = null,
    ) {
    }

    /**      * Executes the from http request operation.      */
    public static function fromHttpRequest(Request $request, CrudRouteContextDTO $routeContext): self
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
