<?php

declare(strict_types=1);

namespace App\Cruding\Controller\Crud;

use App\Cruding\ServiceInterface\Crud\Operation\CrudDeleteOperationInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class CrudDeleteController extends AbstractController
{
    public function __construct(
        private readonly CrudDeleteOperationInterface $operation,
    ) {
    }

    public function __invoke(Request $request): Response
    {
        return $this->operation->handle($request);
    }
}
