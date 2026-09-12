<?php

declare(strict_types=1);

namespace App\Cruding\ServiceInterface;

use App\Cruding\DTO\CrudOwnershipDTO;

/**
 * Defines the contract for ownership resolver within the Cruding component.
 */
interface CrudOwnershipResolverInterface
{
    /**      * Executes the resolve operation.      */
    public function resolve(?object $object): CrudOwnershipDTO;
}
