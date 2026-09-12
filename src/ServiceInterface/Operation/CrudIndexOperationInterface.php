<?php

declare(strict_types=1);

namespace App\Cruding\ServiceInterface\Operation;

use App\Cruding\Value\Resource\CrudResourceContract;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Defines the contract for index operation within the Cruding component.
 */
interface CrudIndexOperationInterface
{
    /**      * Handles the operation represented by this service.      */
    public function handle(Request $request): Response|CrudResourceContract;
}
