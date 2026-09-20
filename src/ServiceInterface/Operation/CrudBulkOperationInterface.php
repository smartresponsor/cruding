<?php

declare(strict_types=1);

namespace App\Cruding\ServiceInterface\Operation;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Defines the explicit Cruding bulk mutation operation boundary.
 */
interface CrudBulkOperationInterface
{
    /** Executes the explicit Cruding bulk mutation operation. */
    public function handle(Request $request): Response;
}
