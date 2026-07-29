<?php

declare(strict_types=1);

namespace App\Cruding\ServiceInterface\Crud;

use App\Cruding\Dto\Crud\CrudCreateOwnerBindingContext;

interface CrudCreateOwnerBinderInterface
{
    public function supports(CrudCreateOwnerBindingContext $context): bool;

    public function bind(CrudCreateOwnerBindingContext $context): void;
}
