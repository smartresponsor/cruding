<?php

declare(strict_types=1);

namespace App\Cruding\Dto\Crud;

final readonly class CrudAccessContext
{
    public function __construct(
        public CrudContext $crud,
        public bool $supportsSlug,
        public bool $supportsId,
        public CrudOwnership $ownership,
        public bool $canView,
        public bool $canEdit,
        public bool $canDelete,
    ) {
    }
}
