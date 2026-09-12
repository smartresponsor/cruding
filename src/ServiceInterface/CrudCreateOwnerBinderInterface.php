<?php

declare(strict_types=1);

namespace App\Cruding\ServiceInterface;

use App\Cruding\DTO\CrudCreateOwnerBindingContextDTO;

/**
 * Defines the contract for create owner binder within the Cruding component.
 */
interface CrudCreateOwnerBinderInterface
{
    /**      * Indicates whether this implementation supports the supplied context.      */
    public function supports(CrudCreateOwnerBindingContextDTO $context): bool;

    /**      * Executes the bind operation.      */
    public function bind(CrudCreateOwnerBindingContextDTO $context): void;
}
