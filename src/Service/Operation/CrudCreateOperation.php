<?php

declare(strict_types=1);

namespace App\Cruding\Service\Operation;

use App\Cruding\Service\Operation\Create\CrudCreateFlow;
use App\Cruding\ServiceInterface\Operation\CrudCreateOperationInterface;
use App\Cruding\Value\Resource\CrudResourceContract;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Provides the create operation responsibility within the Cruding component.
 */
final readonly class CrudCreateOperation implements CrudCreateOperationInterface
{
    public function __construct(
        private CrudCreateFlow $flow,
    ) {
    }

    /**      * Handles the operation represented by this service.      */
    public function handle(Request $request): Response|CrudResourceContract
    {
        return $this->flow->handle($request);
    }
}
