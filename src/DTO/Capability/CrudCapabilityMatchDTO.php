<?php

declare(strict_types=1);

namespace App\Cruding\DTO\Capability;

/**
 * Carries capability match data across Cruding processing boundaries.
 */
final readonly class CrudCapabilityMatchDTO
{
    public function __construct(
        public string $capability,
        public bool $supported,
        public string $source = 'none',
        public ?string $accessor = null,
        public ?string $accessorType = null,
        public ?string $interfaceName = null,
    ) {
    }
}
