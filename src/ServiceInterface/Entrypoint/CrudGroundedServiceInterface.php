<?php

declare(strict_types=1);

namespace App\Cruding\ServiceInterface\Entrypoint;

use App\Cruding\DTO\Entrypoint\CrudServiceContextDTO;

/**
 * Defines the contract for grounded service within the Cruding component.
 */
interface CrudGroundedServiceInterface extends CrudServiceInterface
{
    /**      * Indicates whether grounded.      */
    public function isGrounded(CrudServiceContextDTO $context): bool;
}
