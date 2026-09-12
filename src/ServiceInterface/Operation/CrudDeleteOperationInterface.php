<?php

declare(strict_types=1);

namespace App\Cruding\ServiceInterface\Operation;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Defines the contract for delete operation within the Cruding component.
 */
interface CrudDeleteOperationInterface
{
    /**      * Handles the operation represented by this service.      */
    public function handle(Request $request): Response;
}
