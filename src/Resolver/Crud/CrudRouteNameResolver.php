<?php

declare(strict_types=1);

namespace App\Cruding\Resolver\Crud;

use App\Cruding\Dto\Crud\CrudContext;
use App\Cruding\ServiceInterface\Crud\CrudRouteNameResolverInterface;

final class CrudRouteNameResolver implements CrudRouteNameResolverInterface
{
    public const ROUTE_NAME = 'cruding_tokenized_catch_all';

    public function resolveIndex(CrudContext $context): string
    {
        return self::ROUTE_NAME;
    }

    public function resolveNew(CrudContext $context): string
    {
        return self::ROUTE_NAME;
    }

    public function resolveShow(CrudContext $context, ?string $identifierField = null): string
    {
        return self::ROUTE_NAME;
    }

    public function resolveEdit(CrudContext $context, ?string $identifierField = null): string
    {
        return self::ROUTE_NAME;
    }

    public function resolveDelete(CrudContext $context, ?string $identifierField = null): string
    {
        return self::ROUTE_NAME;
    }

    /**
     * @return array<string, string|int>
     */
    public function parameters(
        CrudContext $context,
        string|int|null $identifierValue = null,
        ?string $identifierField = null,
        ?string $operation = null,
    ): array {
        $operation ??= $context->operation;
        $field = $identifierField ?? $context->identifierField;
        $value = $identifierValue ?? $context->identifierValue;
        $segments = [$context->resourcePath];

        if ('index' === $operation) {
            return ['crudPath' => $context->resourcePath];
        }

        if ('show' === $operation && null !== $value) {
            return ['crudPath' => $context->resourcePath.'/'.$value];
        }

        $segments[] = $operation;
        if (null !== $value && '' !== $field) {
            $segments[] = (string) $value;
        }

        return ['crudPath' => implode('/', array_filter($segments, static fn (string $segment): bool => '' !== trim($segment, '/')))];
    }
}
