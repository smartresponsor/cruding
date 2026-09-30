<?php

declare(strict_types=1);

namespace App\Cruding\Service\Operation;

use App\Cruding\ServiceInterface\Operation\CrudBulkOperationInterface;
use App\Cruding\ServiceInterface\Operation\CrudCreateOperationInterface;
use App\Cruding\ServiceInterface\Operation\CrudDeleteOperationInterface;
use App\Cruding\ServiceInterface\Operation\CrudEditOperationInterface;
use App\Cruding\ServiceInterface\Operation\CrudIndexOperationInterface;
use App\Cruding\ServiceInterface\Operation\CrudPageOperationInterface;
use App\Cruding\ServiceInterface\Operation\CrudShowOperationInterface;
use App\Cruding\ValueObject\Resource\CrudResourceContract;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Dispatches generic web CRUD operations to their typed operation services.
 */
final readonly class CrudOperationDispatcher
{
    private const HANDLER_BY_OPERATION = [
        'index' => 'index',
        'show' => 'show',
        'read' => 'show',
        'page' => 'page',
        'new' => 'create',
        'create' => 'create',
        'import' => 'create',
        'bulk' => 'bulk',
        'edit' => 'edit',
        'update' => 'edit',
        'archive' => 'edit',
        'restore' => 'edit',
        'duplicate' => 'edit',
        'delete' => 'delete',
    ];

    public function __construct(
        private CrudIndexOperationInterface $indexOperation,
        private CrudShowOperationInterface $showOperation,
        private CrudPageOperationInterface $pageOperation,
        private CrudBulkOperationInterface $bulkOperation,
        private CrudCreateOperationInterface $createOperation,
        private CrudEditOperationInterface $editOperation,
        private CrudDeleteOperationInterface $deleteOperation,
    ) {
    }

    /** Indicates whether the operation has a generic CRUD handler. */
    public function supports(string $operation): bool
    {
        return isset(self::HANDLER_BY_OPERATION[$operation]);
    }

    /** Dispatches a supported operation to its typed handler. */
    public function handle(string $operation, Request $request): Response|CrudResourceContract
    {
        return match (self::HANDLER_BY_OPERATION[$operation] ?? null) {
            'index' => $this->indexOperation->handle($request),
            'show' => $this->showOperation->handle($request),
            'page' => $this->pageOperation->handle($request),
            'bulk' => $this->bulkOperation->handle($request),
            'create' => $this->createOperation->handle($request),
            'edit' => $this->editOperation->handle($request),
            'delete' => $this->deleteOperation->handle($request),
            default => throw new \InvalidArgumentException(sprintf('Unsupported CRUD operation "%s".', $operation)),
        };
    }
}
