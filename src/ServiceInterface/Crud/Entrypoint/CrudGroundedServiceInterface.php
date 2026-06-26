<?php

declare(strict_types=1);

namespace App\Cruding\ServiceInterface\Crud\Entrypoint;

use App\Cruding\Dto\Crud\Entrypoint\CrudServiceContext;

interface CrudGroundedServiceInterface extends CrudServiceInterface
{
    public function isGrounded(CrudServiceContext $context): bool;
}
