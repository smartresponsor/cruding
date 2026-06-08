<?php

declare(strict_types=1);

namespace App\Cruding\Service\Crud;

use App\Cruding\Dto\Crud\CrudContext;
use App\Cruding\ServiceInterface\Crud\CrudRouteNameResolverInterface;

final class CrudRouteNameResolver implements CrudRouteNameResolverInterface
{
    public function resolveIndex(CrudContext $context): string
    {
        return 'cruding_index';
    }

    public function resolveNew(CrudContext $context): string
    {
        return 'cruding_new';
    }

    public function resolveShow(CrudContext $context, ?string $identifierField = null): string
    {
        $field = $identifierField ?? $context->identifierField;

        return 'id' === $field ? 'cruding_show_operation_id' : 'cruding_show_operation_slug';
    }

    public function resolveEdit(CrudContext $context, ?string $identifierField = null): string
    {
        $field = $identifierField ?? $context->identifierField;

        return 'id' === $field ? 'cruding_edit_id' : 'cruding_edit_slug';
    }

    public function resolveDelete(CrudContext $context, ?string $identifierField = null): string
    {
        $field = $identifierField ?? $context->identifierField;

        return 'id' === $field ? 'cruding_delete_id' : 'cruding_delete_slug';
    }

    /**
     * @return array<string, string|int>
     */
    public function parameters(CrudContext $context, string|int|null $identifierValue = null, ?string $identifierField = null): array
    {
        $field = $identifierField ?? $context->identifierField;
        $parameters = ['resourcePath' => $context->resourcePath];
        $value = $identifierValue ?? $context->identifierValue;

        if (null !== $value) {
            $parameters[$field] = $value;
        }

        return $parameters;
    }
}
