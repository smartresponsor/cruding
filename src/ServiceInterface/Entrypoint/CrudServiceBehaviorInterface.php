<?php

declare(strict_types=1);

namespace App\Cruding\ServiceInterface\Entrypoint;

use App\Cruding\DTO\Entrypoint\CrudServiceContextDTO;
use App\Cruding\DTO\Entrypoint\CrudServiceResultDTO;

/**
 * Defines the contract for service behavior within the Cruding component.
 */
interface CrudServiceBehaviorInterface
{
    /**      * Executes the operation represented by this service.      */
    public function execute(CrudServiceContextDTO $context): CrudServiceResultDTO;
}
