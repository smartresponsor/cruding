<?php

declare(strict_types=1);

namespace App\Cruding\Resolver;

use App\Cruding\DTO\CrudContextDTO;
use App\Cruding\ServiceInterface\CrudRouteNameResolverInterface;

/**
 * Resolves route name resolver for Cruding request processing.
 */
final class CrudRouteNameResolver implements CrudRouteNameResolverInterface
{
    public const ROUTE_NAME = 'cruding_tokenized_catch_all';

    /**      * Resolves index.      */
    public function resolveIndex(CrudContextDTO $context): string
    {
        return self::ROUTE_NAME;
    }

    /**      * Resolves new.      */
    public function resolveNew(CrudContextDTO $context): string
    {
        return self::ROUTE_NAME;
    }

    /**      * Resolves show.      */
    public function resolveShow(CrudContextDTO $context, ?string $identifierField = null): string
    {
        return self::ROUTE_NAME;
    }

    /**      * Resolves edit.      */
    public function resolveEdit(CrudContextDTO $context, ?string $identifierField = null): string
    {
        return self::ROUTE_NAME;
    }

    /**      * Resolves delete.      */
    public function resolveDelete(CrudContextDTO $context, ?string $identifierField = null): string
    {
        return self::ROUTE_NAME;
    }

    /**
     * @return array<string, string|int>
     */
    public function parameters(
        CrudContextDTO $context,
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

        if ('new' === $operation) {
            return ['crudPath' => $context->resourcePath.'/new'];
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
