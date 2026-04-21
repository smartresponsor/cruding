<?php

declare(strict_types=1);

namespace App\Cruding\ServiceInterface\Crud;

use App\Cruding\Dto\Crud\CrudContext;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

interface CrudApiResponderInterface
{
    /**
     * @param list<object> $objects
     */
    public function collection(CrudContext $context, array $objects): JsonResponse;

    public function item(CrudContext $context, object $object, int $status = JsonResponse::HTTP_OK): JsonResponse;

    /**
     * @return JsonResponse<array{resource: string, deleted: true}>
     */
    public function deleted(CrudContext $context): JsonResponse;

    /**
     * @return JsonResponse<array{resource: string, errors: array<int, array{field: string, message: string}>}>
     */
    public function validationError(CrudContext $context, FormInterface $form): JsonResponse;
}
