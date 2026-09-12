<?php

declare(strict_types=1);

namespace App\Cruding\ServiceInterface\Entrypoint;

use App\Cruding\DTO\Entrypoint\CrudServiceContextDTO;
use App\Cruding\DTO\Entrypoint\CrudServiceResultDTO;
use App\Cruding\ValueObject\Resource\CrudResourceContract;
use Symfony\Component\HttpFoundation\Response;

/**
 * Defines the contract for post service within the Cruding component.
 */
interface CrudPostServiceInterface extends CrudServiceInterface
{
    /**      * Executes the post operation.      */
    public function post(CrudServiceContextDTO $context): CrudServiceResultDTO|Response|CrudResourceContract|null;
}
