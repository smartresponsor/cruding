<?php

declare(strict_types=1);

namespace App\Cruding\Service\Crud;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final readonly class CrudNotFoundResponseFactory
{
    public function create(Request $request, string $reason, array $metadata = []): Response
    {
        return new JsonResponse([
            'ok' => false,
            'component' => 'cruding',
            'reason' => $reason,
            'resourcePath' => (string) $request->attributes->get('resourcePath', ''),
            'operation' => (string) $request->attributes->get('_crud_operation', 'unknown'),
            'surface' => (string) $request->attributes->get('_crud_surface', 'public'),
            'diagnostics' => $this->diagnostics($request, $metadata),
        ], Response::HTTP_NOT_FOUND);
    }

    public function badRequest(Request $request, string $reason, array $metadata = []): Response
    {
        return new JsonResponse([
            'ok' => false,
            'component' => 'cruding',
            'reason' => $reason,
            'resourcePath' => (string) $request->attributes->get('resourcePath', ''),
            'operation' => (string) $request->attributes->get('_crud_operation', 'unknown'),
            'surface' => (string) $request->attributes->get('_crud_surface', 'public'),
            'diagnostics' => $this->diagnostics($request, $metadata),
        ], Response::HTTP_BAD_REQUEST);
    }

    /**
     * @param array<string, mixed> $metadata
     *
     * @return array<string, mixed>
     */
    private function diagnostics(Request $request, array $metadata): array
    {
        return array_filter(array_merge($this->requestDiagnostics($request), $metadata), static fn (mixed $value): bool => null !== $value && [] !== $value);
    }

    /**
     * @return array<string, mixed>
     */
    private function requestDiagnostics(Request $request): array
    {
        $routeName = $request->attributes->get('_route');
        $routeName = is_string($routeName) && '' !== $routeName ? $routeName : null;

        return [
            'path' => $request->getPathInfo(),
            'matchedRoute' => $routeName,
            'routeFamily' => $this->routeFamily($routeName),
            'routeParameters' => $this->routeParameters($request),
            'interpretation' => $this->interpretation($routeName, $request),
        ];
    }

    private function routeFamily(?string $routeName): ?string
    {
        if (null === $routeName) {
            return null;
        }

        if (str_starts_with($routeName, 'cruding_surface_')) {
            return 'surface';
        }

        if (str_starts_with($routeName, 'cruding_api_')) {
            return 'api_crud';
        }

        if (str_starts_with($routeName, 'cruding_')) {
            return 'classic_crud';
        }

        return 'host';
    }

    private function interpretation(?string $routeName, Request $request): ?string
    {
        $family = $this->routeFamily($routeName);
        if ('surface' === $family) {
            return 'Cruding surface grammar matched; a surface provider, generic fallback, or route-map entry must serve it.';
        }

        if ('classic_crud' === $family) {
            return 'Classic CRUD grammar matched; Cruding will resolve resourcePath as an entity/resource, not as a multi-token business surface.';
        }

        if ('api_crud' === $family) {
            return 'API CRUD grammar matched; Cruding will resolve resourcePath as an API resource.';
        }

        if (null === $routeName && '' !== $request->getPathInfo()) {
            return 'No Cruding route matched the URI before controller execution.';
        }

        return null;
    }

    /**
     * @return array<string, string|int|float|bool|null>
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

        ksort($parameters);

        return $parameters;
    }
}
