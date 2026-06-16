<?php

declare(strict_types=1);

namespace App\Cruding\ServiceInterface\Crud\Entrypoint;

use App\Cruding\Dto\Crud\Entrypoint\CrudEntrypointContext;
use App\Cruding\Dto\Crud\Entrypoint\CrudEntrypointResult;

interface CrudEntrypointBehaviorInterface
{
    public function execute(CrudEntrypointContext $context): CrudEntrypointResult;
}
