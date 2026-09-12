<?php

declare(strict_types=1);

namespace App\Cruding\ServiceInterface;

use App\Cruding\DTO\CrudContextDTO;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Defines the contract for api responder within the Cruding component.
 */
interface CrudApiResponderInterface
{
    /**
     * @param list<object> $objects
     */
    public function collection(CrudContextDTO $context, array $objects): JsonResponse;

    /**      * Executes the item operation.      */
    public function item(CrudContextDTO $context, object $object, int $status = JsonResponse::HTTP_OK): JsonResponse;

    /**
     * @return JsonResponse<array{resource: string, deleted: true}>
     */
    public function deleted(CrudContextDTO $context): JsonResponse;

    /**      * Executes the not found operation.      */
    public function notFound(string $resourcePath, string $detail = 'Resource not found.'): JsonResponse;

    /**
     * @return JsonResponse<array{resource: string, errors: array<int, array{field: string, message: string}>}>
     */
    public function validationError(CrudContextDTO $context, FormInterface $form): JsonResponse;
}
