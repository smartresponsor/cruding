<?php

declare(strict_types=1);

namespace App\Cruding\Controller;

use App\Cruding\ServiceInterface\Operation\CrudIndexOperationInterface;
use App\Cruding\ValueObject\Resource\CrudResourceContract;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Handles index controller HTTP requests at the Cruding controller boundary.
 */
final class CrudIndexController extends AbstractController
{
    public function __construct(
        private readonly CrudIndexOperationInterface $operation,
    ) {
    }

    /**      * Handles the invokable HTTP or application entrypoint.      */
    public function __invoke(Request $request): Response|CrudResourceContract
    {
        return $this->operation->handle($request);
    }
}
