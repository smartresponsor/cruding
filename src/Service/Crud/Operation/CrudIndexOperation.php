<?php

declare(strict_types=1);

namespace App\Cruding\Service\Crud\Operation;

use App\Cruding\ServiceInterface\Crud\Operation\CrudIndexOperationInterface;
use App\Cruding\Value\Surface\CrudSurfaceContract;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class CrudIndexOperation implements CrudIndexOperationInterface
{
    public function handle(Request $request): Response|CrudSurfaceContract
    {
        return new Response(status: Response::HTTP_NOT_IMPLEMENTED);
    }
}
