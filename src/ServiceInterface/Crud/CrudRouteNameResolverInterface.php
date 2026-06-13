<?php

declare(strict_types=1);

namespace App\Cruding\ServiceInterface\Crud;

use App\Cruding\Dto\Crud\CrudContext;

interface CrudRouteNameResolverInterface
{
    public function resolveIndex(CrudContext $context): string;

    public function resolveNew(CrudContext $context): string;

    public function resolveShow(CrudContext $context, ?string $identifierField = null): string;

    public function resolveEdit(CrudContext $context, ?string $identifierField = null): string;

    public function resolveDelete(CrudContext $context, ?string $identifierField = null): string;

    /**
     * @return array<string, string|int>
     */
    public function parameters(CrudContext $context, string|int|null $identifierValue = null, ?string $identifierField = null, ?string $operation = null): array;
}
