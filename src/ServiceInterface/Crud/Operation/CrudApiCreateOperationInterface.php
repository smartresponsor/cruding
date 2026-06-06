<?php

declare(strict_types=1);

namespace App\Cruding\ServiceInterface\Crud\Operation;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

interface CrudApiCreateOperationInterface
{
    public function handle(Request $request): Response;
}
