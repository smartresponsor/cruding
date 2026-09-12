<?php

declare(strict_types=1);

namespace App\Cruding\Dto;

/**
 * Carries access context data across Cruding processing boundaries.
 */
final readonly class CrudAccessContextDTO
{
    public function __construct(
        public CrudContextDTO $crud,
        public bool $supportsSlug,
        public bool $supportsId,
        public CrudOwnershipDTO $ownership,
        public bool $canView,
        public bool $canEdit,
        public bool $canDelete,
    ) {
    }
}
