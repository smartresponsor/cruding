<?php

declare(strict_types=1);

namespace App\Cruding\ServiceInterface\Crud\Entrypoint;

use App\Cruding\Dto\Crud\CrudContext;
use App\Cruding\Dto\Crud\Entrypoint\CrudEntrypointResult;
use App\Cruding\Value\Surface\CrudSurfaceContract;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

interface CrudServiceDispatcherInterface
{
    public function run(Request $request, CrudContext $crudContext, ?object $object = null): CrudEntrypointResult;

    public function tryRun(Request $request, CrudContext $crudContext, ?object $object = null): Response|CrudSurfaceContract|null;
}
