<?php

declare(strict_types=1);

namespace App\Cruding\ServiceInterface\Crud\Entrypoint;

use App\Cruding\Dto\Crud\Entrypoint\CrudEntrypointContext;
use App\Cruding\Dto\Crud\Entrypoint\CrudEntrypointResult;
use App\Cruding\Value\Surface\CrudSurfaceContract;
use Symfony\Component\HttpFoundation\Response;

interface CrudPostEntrypointInterface extends CrudEntrypointServiceInterface
{
    public function post(CrudEntrypointContext $context): CrudEntrypointResult|Response|CrudSurfaceContract|null;
}
