<?php

declare(strict_types=1);

namespace App\Cruding\ServiceInterface;

use App\Cruding\DTO\CrudContextDTO;

/**
 * Defines the contract for object finder within the Cruding component.
 */
interface CrudObjectFinderInterface
{
    /**      * Finds one.      */
    public function findOne(CrudContextDTO $context): ?object;

    /**
     * @return list<object>
     */
    public function findAll(CrudContextDTO $context): array;
}
