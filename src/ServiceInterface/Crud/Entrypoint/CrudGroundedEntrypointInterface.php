<?php

declare(strict_types=1);

namespace App\Cruding\ServiceInterface\Crud\Entrypoint;

use App\Cruding\Dto\Crud\Entrypoint\CrudEntrypointContext;

interface CrudGroundedEntrypointInterface extends CrudEntrypointServiceInterface
{
    public function isGrounded(CrudEntrypointContext $context): bool;
}
