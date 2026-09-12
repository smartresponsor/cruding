<?php

declare(strict_types=1);

namespace App\Cruding\ServiceInterface;

use App\Cruding\DTO\CrudMutationLifecycleContextDTO;

/**
 * Defines the contract for mutation lifecycle subscriber within the Cruding component.
 */
interface CrudMutationLifecycleSubscriberInterface
{
    /**      * Indicates whether this implementation supports the supplied context.      */
    public function supports(CrudMutationLifecycleContextDTO $context): bool;

    /**      * Executes the before operation.      */
    public function before(CrudMutationLifecycleContextDTO $context): void;

    /**      * Executes the after operation.      */
    public function after(CrudMutationLifecycleContextDTO $context): void;
}
