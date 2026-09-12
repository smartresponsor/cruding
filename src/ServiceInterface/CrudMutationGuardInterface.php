<?php

declare(strict_types=1);

namespace App\Cruding\ServiceInterface;

use App\Cruding\DTO\CrudAccessContextDTO;

/**
 * Defines the contract for mutation guard within the Cruding component.
 */
interface CrudMutationGuardInterface
{
    /**      * Asserts can edit.      */
    public function assertCanEdit(CrudAccessContextDTO $access): void;

    /**      * Asserts can delete.      */
    public function assertCanDelete(CrudAccessContextDTO $access): void;
}
