<?php

declare(strict_types=1);

namespace App\Cruding\ServiceInterface\Entrypoint;

use App\Cruding\DTO\Entrypoint\CrudServiceContextDTO;
use App\Cruding\DTO\Entrypoint\CrudServiceResultDTO;
use App\Cruding\Value\Resource\CrudResourceContract;
use Symfony\Component\HttpFoundation\Response;

/**
 * Defines the contract for get service within the Cruding component.
 */
interface CrudGetServiceInterface extends CrudServiceInterface
{
    /**      * Executes the get operation.      */
    public function get(CrudServiceContextDTO $context): CrudServiceResultDTO|Response|CrudResourceContract|null;
}
