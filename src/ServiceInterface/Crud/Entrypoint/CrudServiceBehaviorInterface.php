<?php

declare(strict_types=1);

namespace App\Cruding\ServiceInterface\Crud\Entrypoint;

use App\Cruding\Dto\Crud\Entrypoint\CrudServiceContext;
use App\Cruding\Dto\Crud\Entrypoint\CrudServiceResult;

interface CrudServiceBehaviorInterface
{
    public function execute(CrudServiceContext $context): CrudServiceResult;
}
