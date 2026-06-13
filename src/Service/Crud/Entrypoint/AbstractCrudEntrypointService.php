<?php

declare(strict_types=1);

namespace App\Cruding\Service\Crud\Entrypoint;

use App\Cruding\Dto\Crud\Entrypoint\CrudEntrypointContext;
use App\Cruding\Dto\Crud\Entrypoint\CrudEntrypointResult;
use App\Cruding\ServiceInterface\Crud\Entrypoint\CrudDeleteEntrypointInterface;
use App\Cruding\ServiceInterface\Crud\Entrypoint\CrudEntrypointServiceInterface;
use App\Cruding\ServiceInterface\Crud\Entrypoint\CrudGetEntrypointInterface;
use App\Cruding\ServiceInterface\Crud\Entrypoint\CrudGroundedEntrypointInterface;
use App\Cruding\ServiceInterface\Crud\Entrypoint\CrudPatchEntrypointInterface;
use App\Cruding\ServiceInterface\Crud\Entrypoint\CrudPostEntrypointInterface;
use App\Cruding\ServiceInterface\Crud\Entrypoint\CrudPutEntrypointInterface;
use App\Cruding\Value\Surface\CrudSurfaceContract;
use Symfony\Component\HttpFoundation\Response;

abstract class AbstractCrudEntrypointService implements CrudEntrypointServiceInterface, CrudGroundedEntrypointInterface, CrudGetEntrypointInterface, CrudPostEntrypointInterface, CrudPutEntrypointInterface, CrudPatchEntrypointInterface, CrudDeleteEntrypointInterface
{
    public function isGrounded(CrudEntrypointContext $context): bool
    {
        return true;
    }

    public function get(CrudEntrypointContext $context): CrudEntrypointResult|Response|CrudSurfaceContract|null
    {
        return null;
    }

    public function post(CrudEntrypointContext $context): CrudEntrypointResult|Response|CrudSurfaceContract|null
    {
        return null;
    }

    public function put(CrudEntrypointContext $context): CrudEntrypointResult|Response|CrudSurfaceContract|null
    {
        return null;
    }

    public function patch(CrudEntrypointContext $context): CrudEntrypointResult|Response|CrudSurfaceContract|null
    {
        return null;
    }

    public function delete(CrudEntrypointContext $context): CrudEntrypointResult|Response|CrudSurfaceContract|null
    {
        return null;
    }
}
