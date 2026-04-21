<?php

declare(strict_types=1);

namespace App\Cruding\ServiceInterface\Crud;

use App\Cruding\Dto\Crud\CrudContext;

interface CrudObjectFinderInterface
{
    public function findOne(CrudContext $context): ?object;

    /**
     * @return list<object>
     */
    public function findAll(CrudContext $context): array;
}
