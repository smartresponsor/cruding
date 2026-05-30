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
            'diagnostics' => array_filter($metadata, static fn (mixed $value): bool => null !== $value),
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
            'diagnostics' => array_filter($metadata, static fn (mixed $value): bool => null !== $value),
        ], Response::HTTP_BAD_REQUEST);
    }
}
