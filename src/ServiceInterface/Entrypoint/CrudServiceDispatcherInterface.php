<?php

declare(strict_types=1);

namespace App\Cruding\ServiceInterface\Entrypoint;

use App\Cruding\DTO\CrudContextDTO;
use App\Cruding\DTO\Entrypoint\CrudServiceResultDTO;
use App\Cruding\Value\Resource\CrudResourceContract;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Defines the contract for service dispatcher within the Cruding component.
 */
interface CrudServiceDispatcherInterface
{
    /**      * Runs the selected Cruding workflow.      */
    public function run(Request $request, CrudContextDTO $crudContext, ?object $object = null): CrudServiceResultDTO;

    /**      * Attempts to run the selected Cruding workflow without forcing a result.      */
    public function tryRun(Request $request, CrudContextDTO $crudContext, ?object $object = null): Response|CrudResourceContract|null;
}
