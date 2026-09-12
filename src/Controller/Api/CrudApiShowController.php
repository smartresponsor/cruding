<?php

declare(strict_types=1);

namespace App\Cruding\Controller\Api;

use App\Cruding\ServiceInterface\Operation\CrudApiShowOperationInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Handles api show controller HTTP requests at the Cruding controller boundary.
 */
final class CrudApiShowController extends AbstractController
{
    public function __construct(
        private readonly CrudApiShowOperationInterface $operation,
    ) {
    }

    /**      * Handles the invokable HTTP or application entrypoint.      */
    public function __invoke(Request $request): Response
    {
        return $this->operation->handle($request);
    }
}
