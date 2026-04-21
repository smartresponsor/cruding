<?php

declare(strict_types=1);

namespace App\Cruding\ServiceInterface\Crud;

interface CrudTemplateResolverInterface
{
    public function resolvePrefix(string $resourcePath): string;
}
