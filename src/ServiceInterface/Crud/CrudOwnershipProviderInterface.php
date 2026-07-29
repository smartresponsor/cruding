<?php

declare(strict_types=1);

namespace App\Cruding\ServiceInterface\Crud;

use App\Cruding\Dto\Crud\CrudOwnership;
use App\Cruding\Dto\Crud\CrudOwnershipResolutionContext;

interface CrudOwnershipProviderInterface
{
    public function supports(CrudOwnershipResolutionContext $context): bool;

    public function resolve(CrudOwnershipResolutionContext $context): CrudOwnership;
}
