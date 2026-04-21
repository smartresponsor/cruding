<?php

declare(strict_types=1);

namespace App\Cruding\ServiceInterface\Crud;

use App\Cruding\Dto\Crud\CrudContext;
use Symfony\Component\HttpFoundation\Request;

interface CrudContextResolverInterface
{
    public function resolve(Request $request): CrudContext;
}
