<?php

declare(strict_types=1);

namespace App\Cruding\ServiceInterface\Crud\Operation;

use App\Cruding\Value\Resource\CrudResourceContract;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

interface CrudCreateOperationInterface
{
    public function handle(Request $request): Response|CrudResourceContract;
}
