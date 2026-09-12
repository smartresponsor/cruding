<?php

declare(strict_types=1);

namespace App\Cruding\ServiceInterface\Entrypoint;

use App\Cruding\DTO\Entrypoint\CrudServiceContextDTO;
use App\Cruding\DTO\Entrypoint\CrudServiceResultDTO;
use App\Cruding\Value\Resource\CrudResourceContract;
use Symfony\Component\HttpFoundation\Response;

/**
 * Defines the contract for patch service within the Cruding component.
 */
interface CrudPatchServiceInterface extends CrudServiceInterface
{
    /**      * Executes the patch operation.      */
    public function patch(CrudServiceContextDTO $context): CrudServiceResultDTO|Response|CrudResourceContract|null;
}
