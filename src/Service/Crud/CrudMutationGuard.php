<?php

declare(strict_types=1);

namespace App\Cruding\Service\Crud;

use App\Cruding\Dto\Crud\CrudAccessContext;
use App\Cruding\ServiceInterface\Crud\CrudMutationGuardInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

final class CrudMutationGuard implements CrudMutationGuardInterface
{
    public function assertCanEdit(CrudAccessContext $access): void
    {
        if (!$access->canEdit) {
            throw new AccessDeniedHttpException('You are not allowed to edit this object.');
        }
    }

    public function assertCanDelete(CrudAccessContext $access): void
    {
        if (!$access->canDelete) {
            throw new AccessDeniedHttpException('You are not allowed to delete this object.');
        }
    }
}
