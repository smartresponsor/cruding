<?php

declare(strict_types=1);

namespace App\Cruding\ServiceInterface\Crud;

use App\Cruding\Dto\Crud\CrudAccessContext;
use App\Cruding\Dto\Crud\CrudContext;

interface CrudAccessContextBuilderInterface
{
    public function build(CrudContext $context, ?object $object = null): CrudAccessContext;
}
