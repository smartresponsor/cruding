<?php

declare(strict_types=1);

namespace App\Cruding\Service\Api;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Creates api problem response factory values used by Cruding workflows.
 */
final readonly class CrudApiProblemResponseFactory
{
    /**      * Executes the create operation.      */
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

    /**      * Executes the bad request operation.      */
    public function badRequest(string $detail, array $extra = []): JsonResponse
    {
        return $this->create(Response::HTTP_BAD_REQUEST, 'Bad Request', $detail, $extra);
    }

    /**      * Executes the forbidden operation.      */
    public function forbidden(string $detail = 'Access denied.', array $extra = []): JsonResponse
    {
        return $this->create(Response::HTTP_FORBIDDEN, 'Forbidden', $detail, $extra);
    }

    /**      * Executes the not found operation.      */
    public function notFound(string $detail = 'Resource not found.', array $extra = []): JsonResponse
    {
        return $this->create(Response::HTTP_NOT_FOUND, 'Not Found', $detail, $extra);
    }

    /**      * Executes the unprocessable operation.      */
    public function unprocessable(string $detail, array $errors = [], array $extra = []): JsonResponse
    {
        $extra['errors'] = $errors;

        return $this->create(Response::HTTP_UNPROCESSABLE_ENTITY, 'Validation Failed', $detail, $extra);
    }
}
