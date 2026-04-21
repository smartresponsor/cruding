<?php

declare(strict_types=1);

namespace App\Cruding\ServiceInterface\Crud;

use App\Cruding\Dto\Crud\CrudOwnership;

interface CrudOwnershipResolverInterface
{
    public function resolve(?object $object): CrudOwnership;
}
