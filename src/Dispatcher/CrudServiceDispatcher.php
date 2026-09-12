<?php

declare(strict_types=1);

namespace App\Cruding\Dispatcher;

use App\Cruding\DTO\CrudContextDTO;
use App\Cruding\DTO\Entrypoint\CrudServiceResultDTO;
use App\Cruding\Runner\CrudServiceRunner;
use App\Cruding\ServiceInterface\Entrypoint\CrudServiceDispatcherInterface;
use App\Cruding\Value\Resource\CrudResourceContract;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Provides the service dispatcher responsibility within the Cruding component.
 */
final readonly class CrudServiceDispatcher implements CrudServiceDispatcherInterface
{
    public function __construct(private CrudServiceRunner $operationRunner)
    {
    }

    /**      * Runs the selected Cruding workflow.      */
    public function run(Request $request, CrudContextDTO $crudContext, ?object $object = null): CrudServiceResultDTO
    {
        return $this->operationRunner->run($request, $crudContext, $object);
    }

    /**      * Attempts to run the selected Cruding workflow without forcing a result.      */
    public function tryRun(Request $request, CrudContextDTO $crudContext, ?object $object = null): Response|CrudResourceContract|null
    {
        return $this->operationRunner->tryRun($request, $crudContext, $object);
    }
}
