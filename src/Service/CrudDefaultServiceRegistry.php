<?php

declare(strict_types=1);

namespace App\Cruding\Service;

use App\Cruding\DTO\CrudContextDTO;
use App\Cruding\ServiceInterface\Entrypoint\CrudServiceInterface;

/**
 * Provides the default service registry responsibility within the Cruding component.
 */
final readonly class CrudDefaultServiceRegistry
{
    public function __construct(
        private DefaultCrudIndexService $index,
        private DefaultCrudShowService $show,
        private DefaultCrudCreateService $create,
        private DefaultCrudEditService $edit,
        private DefaultCrudService $generic,
    ) {
    }

    /**      * Executes the for operation.      */
    public function for(CrudContextDTO $context): CrudServiceInterface
    {
        return match ($context->operation) {
            'index' => $this->index,
            'show' => $this->show,
            'new', 'create' => $this->create,
            'edit', 'update' => $this->edit,
            default => $this->generic,
        };
    }
}
