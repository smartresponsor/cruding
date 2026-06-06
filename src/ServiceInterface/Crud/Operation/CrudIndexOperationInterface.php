<?php

declare(strict_types=1);

namespace App\Cruding\ServiceInterface\Crud\Operation;

use App\Cruding\Value\Surface\CrudSurfaceContract;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

interface CrudIndexOperationInterface
{
    public function handle(Request $request): Response|CrudSurfaceContract;
}
