<?php

declare(strict_types=1);

namespace App\Cruding\ServiceInterface\Operation;

use App\Cruding\ValueObject\Resource\CrudResourceContract;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Defines the contract for create operation within the Cruding component.
 */
interface CrudCreateOperationInterface
{
    /**      * Handles the operation represented by this service.      */
    public function handle(Request $request): Response|CrudResourceContract;
}
