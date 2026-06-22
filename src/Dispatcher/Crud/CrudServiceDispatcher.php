<?php

declare(strict_types=1);

namespace App\Cruding\Dispatcher\Crud;

use App\Cruding\Dto\Crud\CrudContext;
use App\Cruding\Dto\Crud\Entrypoint\CrudEntrypointResult;
use App\Cruding\ServiceInterface\Crud\Entrypoint\CrudServiceDispatcherInterface;
use App\Cruding\Value\Surface\CrudSurfaceContract;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final readonly class CrudServiceDispatcher implements CrudServiceDispatcherInterface
{
    public function __construct(private CrudServiceRunner $operationRunner)
    {
    }

    public function run(Request $request, CrudContext $crudContext, ?object $object = null): CrudEntrypointResult
    {
        return $this->operationRunner->run($request, $crudContext, $object);
    }

    public function tryRun(Request $request, CrudContext $crudContext, ?object $object = null): Response|CrudSurfaceContract|null
    {
        return $this->operationRunner->tryRun($request, $crudContext, $object);
    }
}
