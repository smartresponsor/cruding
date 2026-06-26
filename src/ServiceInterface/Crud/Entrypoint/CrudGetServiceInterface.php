<?php

declare(strict_types=1);

namespace App\Cruding\ServiceInterface\Crud\Entrypoint;

use App\Cruding\Dto\Crud\Entrypoint\CrudServiceContext;
use App\Cruding\Dto\Crud\Entrypoint\CrudServiceResult;
use App\Cruding\Value\Resource\CrudResourceContract;
use Symfony\Component\HttpFoundation\Response;

interface CrudGetServiceInterface extends CrudServiceInterface
{
    public function get(CrudServiceContext $context): CrudServiceResult|Response|CrudResourceContract|null;
}
