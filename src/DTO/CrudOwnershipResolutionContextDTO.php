<?php

declare(strict_types=1);

namespace App\Cruding\DTO;

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
