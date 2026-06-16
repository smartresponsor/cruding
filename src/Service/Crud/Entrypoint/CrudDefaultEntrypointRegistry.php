<?php

declare(strict_types=1);

namespace App\Cruding\Service\Crud\Entrypoint;

use App\Cruding\Dto\Crud\CrudContext;
use App\Cruding\ServiceInterface\Crud\Entrypoint\CrudEntrypointServiceInterface;

final readonly class CrudDefaultEntrypointRegistry
{
    public function __construct(
        private DefaultCrudIndexService $index,
        private DefaultCrudShowService $show,
        private DefaultCrudCreateService $create,
        private DefaultCrudEditService $edit,
        private DefaultCrudEntrypointService $generic,
    ) {
    }

    public function for(CrudContext $context): CrudEntrypointServiceInterface
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
