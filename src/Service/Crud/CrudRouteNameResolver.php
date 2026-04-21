<?php

declare(strict_types=1);

namespace App\Cruding\Service\Crud;

use App\Cruding\Dto\Crud\CrudContext;
use App\Cruding\ServiceInterface\Crud\CrudRouteNameResolverInterface;

final class CrudRouteNameResolver implements CrudRouteNameResolverInterface
{
    public function resolveIndex(CrudContext $context): string
    {
        return 'app_crud_index';
    }

    public function resolveShow(CrudContext $context, ?string $identifierField = null): string
    {
        $field = $identifierField ?? $context->identifierField;

        return 'id' === $field ? 'app_crud_show_id' : 'app_crud_show_slug';
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
