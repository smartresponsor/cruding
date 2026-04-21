<?php

declare(strict_types=1);

namespace App\Cruding\ServiceInterface\Crud;

use App\Cruding\Dto\Crud\CrudContext;

interface CrudRouteNameResolverInterface
{
    public function resolveIndex(CrudContext $context): string;

    public function resolveShow(CrudContext $context, ?string $identifierField = null): string;

    /**
     * @return array<string, string|int>
     */
    public function parameters(CrudContext $context, string|int|null $identifierValue = null, ?string $identifierField = null): array;
}
