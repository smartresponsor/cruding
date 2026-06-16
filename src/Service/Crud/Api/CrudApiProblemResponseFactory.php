<?php

declare(strict_types=1);

namespace App\Cruding\Service\Crud\Api;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

final readonly class CrudApiProblemResponseFactory
{
    public function create(
        int $status,
        string $title,
        string $detail,
        array $extra = [],
        string $type = 'about:blank',
    ): JsonResponse {
        return new JsonResponse([
            'type' => $type,
            'title' => $title,
            'status' => $status,
            'detail' => $detail,
            'errors' => $extra['errors'] ?? [],
            'resourcePath' => $extra['resourcePath'] ?? null,
        ], $status, ['Content-Type' => 'application/problem+json']);
    }

    public function badRequest(string $detail, array $extra = []): JsonResponse
    {
        return $this->create(Response::HTTP_BAD_REQUEST, 'Bad Request', $detail, $extra);
    }

    public function forbidden(string $detail = 'Access denied.', array $extra = []): JsonResponse
    {
        return $this->create(Response::HTTP_FORBIDDEN, 'Forbidden', $detail, $extra);
    }

    public function notFound(string $detail = 'Resource not found.', array $extra = []): JsonResponse
    {
        return $this->create(Response::HTTP_NOT_FOUND, 'Not Found', $detail, $extra);
    }

    public function unprocessable(string $detail, array $errors = [], array $extra = []): JsonResponse
    {
        $extra['errors'] = $errors;

        return $this->create(Response::HTTP_UNPROCESSABLE_ENTITY, 'Validation Failed', $detail, $extra);
    }
}
