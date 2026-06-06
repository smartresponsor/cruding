<?php

declare(strict_types=1);

namespace App\Cruding\Controller\Api\Crud;

use App\Cruding\ServiceInterface\Crud\Operation\CrudApiDeleteOperationInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class CrudApiDeleteController extends AbstractController
{
    public function __construct(
        private readonly CrudApiDeleteOperationInterface $operation,
    ) {
    }

    public function __invoke(Request $request): Response
    {
        return $this->operation->handle($request);
    }
}
