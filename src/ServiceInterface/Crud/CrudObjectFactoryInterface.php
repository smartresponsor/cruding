<?php

declare(strict_types=1);

namespace App\Cruding\ServiceInterface\Crud;

interface CrudObjectFactoryInterface
{
    /**
     * @param class-string $entityClass
     */
    public function create(string $entityClass): object;
}
