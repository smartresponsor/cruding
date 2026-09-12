<?php

declare(strict_types=1);

namespace App\Cruding\ServiceInterface;

use App\Cruding\DTO\CrudContextDTO;

/**
 * Defines the contract for route name resolver within the Cruding component.
 */
interface CrudRouteNameResolverInterface
{
    /**      * Resolves index.      */
    public function resolveIndex(CrudContextDTO $context): string;

    /**      * Resolves new.      */
    public function resolveNew(CrudContextDTO $context): string;

    /**      * Resolves show.      */
    public function resolveShow(CrudContextDTO $context, ?string $identifierField = null): string;

    /**      * Resolves edit.      */
    public function resolveEdit(CrudContextDTO $context, ?string $identifierField = null): string;

    /**      * Resolves delete.      */
    public function resolveDelete(CrudContextDTO $context, ?string $identifierField = null): string;

    /**
     * @return array<string, string|int>
     */
    public function parameters(CrudContextDTO $context, string|int|null $identifierValue = null, ?string $identifierField = null, ?string $operation = null): array;
}
