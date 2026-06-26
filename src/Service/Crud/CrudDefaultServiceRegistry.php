<?php

declare(strict_types=1);

namespace App\Cruding\Service\Crud;

use App\Cruding\Dto\Crud\CrudContext;
use App\Cruding\ServiceInterface\Crud\Entrypoint\CrudServiceInterface;

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

    public function for(CrudContext $context): CrudServiceInterface
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
