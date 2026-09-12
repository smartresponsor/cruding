<?php

declare(strict_types=1);

namespace App\Cruding\ServiceInterface;

/**
 * Defines the contract for object factory within the Cruding component.
 */
interface CrudObjectFactoryInterface
{
    /**
     * @param class-string $entityClass
     */
    public function create(string $entityClass): object;
}
