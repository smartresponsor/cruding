<?php

declare(strict_types=1);

namespace App\Cruding\Dto;

/**
 * Carries ownership data across Cruding processing boundaries.
 */
final readonly class CrudOwnershipDTO
{
    public function __construct(
        public bool $supportsOwnership,
        public bool $authenticated,
        public bool $isOwner,
        public bool $isAdmin,
        public ?string $ownerField,
    ) {
    }

    /**      * Indicates whether mutate.      */
    public function canMutate(): bool
    {
        if ($this->isAdmin) {
            return true;
        }

        if (!$this->supportsOwnership) {
            return false;
        }

        return $this->authenticated && $this->isOwner;
    }
}
