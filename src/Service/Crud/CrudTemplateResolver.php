<?php

declare(strict_types=1);

namespace App\Cruding\Service\Crud;

use App\Cruding\ServiceInterface\Crud\CrudTemplateResolverInterface;

final class CrudTemplateResolver implements CrudTemplateResolverInterface
{
    public function resolvePrefix(string $resourcePath): string
    {
        return '@Cruding/crud';
    }
}
