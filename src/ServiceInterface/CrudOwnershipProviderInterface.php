<?php

declare(strict_types=1);

namespace App\Cruding\ServiceInterface;

use App\Cruding\DTO\CrudOwnershipDTO;
use App\Cruding\DTO\CrudOwnershipResolutionContextDTO;

/**
 * Defines the contract for ownership provider within the Cruding component.
 */
interface CrudOwnershipProviderInterface
{
    /**      * Indicates whether this implementation supports the supplied context.      */
    public function supports(CrudOwnershipResolutionContextDTO $context): bool;

    /**      * Executes the resolve operation.      */
    public function resolve(CrudOwnershipResolutionContextDTO $context): CrudOwnershipDTO;
}
