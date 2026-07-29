<?php

declare(strict_types=1);

namespace App\Cruding\Service\Crud\Operation;

use App\Cruding\Service\Crud\Operation\Create\CrudCreateFlow;
use App\Cruding\ServiceInterface\Crud\Operation\CrudCreateOperationInterface;
use App\Cruding\Value\Resource\CrudResourceContract;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final readonly class CrudCreateOperation implements CrudCreateOperationInterface
{
    public function __construct(
        private CrudCreateFlow $flow,
    ) {
    }

    public function handle(Request $request): Response|CrudResourceContract
    {
        return $this->flow->handle($request);
    }
}
