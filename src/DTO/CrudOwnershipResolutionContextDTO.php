<?php

declare(strict_types=1);

namespace App\Cruding\Dto;

/**
 * Carries ownership resolution context data across Cruding processing boundaries.
 */
final readonly class CrudOwnershipResolutionContextDTO
{
    public function __construct(
        public object $object,
        public ?object $actor,
        public bool $isAdmin,
    ) {
    }
}
