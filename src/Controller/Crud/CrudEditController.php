<?php

declare(strict_types=1);

namespace App\Cruding\Controller\Crud;

use App\Cruding\ServiceInterface\Crud\Operation\CrudEditOperationInterface;
use App\Cruding\Value\Surface\CrudSurfaceContract;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class CrudEditController extends AbstractController
{
    public function __construct(
        private readonly CrudEditOperationInterface $operation,
    ) {
    }

    public function __invoke(Request $request): Response|CrudSurfaceContract
    {
        return $this->operation->handle($request);
    }
}
