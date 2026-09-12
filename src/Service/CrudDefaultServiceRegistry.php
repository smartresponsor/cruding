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
        private CrudDefaultIndexService $index,
        private CrudDefaultShowService $show,
        private CrudDefaultCreateService $create,
        private CrudDefaultEditService $edit,
        private CrudDefaultService $generic,
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
