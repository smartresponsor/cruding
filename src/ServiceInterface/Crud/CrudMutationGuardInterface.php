<?php

declare(strict_types=1);

namespace App\Cruding\ServiceInterface\Crud;

use App\Cruding\Dto\Crud\CrudAccessContext;

interface CrudMutationGuardInterface
{
    public function assertCanEdit(CrudAccessContext $access): void;

    public function assertCanDelete(CrudAccessContext $access): void;
}
