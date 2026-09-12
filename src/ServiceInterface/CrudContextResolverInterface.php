<?php

declare(strict_types=1);

namespace App\Cruding\ServiceInterface;

use App\Cruding\DTO\CrudContextDTO;
use Symfony\Component\HttpFoundation\Request;

/**
 * Defines the contract for context resolver within the Cruding component.
 */
interface CrudContextResolverInterface
{
    /**      * Executes the resolve operation.      */
    public function resolve(Request $request): CrudContextDTO;

    /**      * Executes the try resolve operation.      */
    public function tryResolve(Request $request): ?CrudContextDTO;
}
