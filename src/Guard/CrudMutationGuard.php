<?php

declare(strict_types=1);

namespace App\Cruding\Guard;

use App\Cruding\DTO\CrudAccessContextDTO;
use App\Cruding\ServiceInterface\CrudMutationGuardInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Enforces mutation guard rules at the Cruding runtime boundary.
 */
final class CrudMutationGuard implements CrudMutationGuardInterface
{
    /**      * Asserts can edit.      */
    public function assertCanEdit(CrudAccessContextDTO $access): void
    {
        if (!$access->canEdit) {
            throw new AccessDeniedHttpException('You are not allowed to edit this object.');
        }
    }

    /**      * Asserts can delete.      */
    public function assertCanDelete(CrudAccessContextDTO $access): void
    {
        if (!$access->canDelete) {
            throw new AccessDeniedHttpException('You are not allowed to delete this object.');
        }
    }
}
