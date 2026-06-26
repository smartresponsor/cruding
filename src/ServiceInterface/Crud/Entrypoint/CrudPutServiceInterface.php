<?php

declare(strict_types=1);

namespace App\Cruding\ServiceInterface\Crud\Entrypoint;

use App\Cruding\Dto\Crud\Entrypoint\CrudServiceContext;
use App\Cruding\Dto\Crud\Entrypoint\CrudServiceResult;
use App\Cruding\Value\Resource\CrudResourceContract;
use Symfony\Component\HttpFoundation\Response;

interface CrudPutServiceInterface extends CrudServiceInterface
{
    public function put(CrudServiceContext $context): CrudServiceResult|Response|CrudResourceContract|null;
}
