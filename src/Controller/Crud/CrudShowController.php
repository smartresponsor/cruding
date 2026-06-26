<?php

declare(strict_types=1);

namespace App\Cruding\Controller\Crud;

use App\Cruding\ServiceInterface\Crud\Operation\CrudShowOperationInterface;
use App\Cruding\Value\Resource\CrudResourceContract;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class CrudShowController extends AbstractController
{
    public function __construct(
        private readonly CrudShowOperationInterface $operation,
    ) {
    }

    public function __invoke(Request $request): Response|CrudResourceContract
    {
        return $this->operation->handle($request);
    }
}
