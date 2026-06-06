<?php

declare(strict_types=1);

namespace App\Cruding\Dto\Capability;

final readonly class CrudCapabilityMatch
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
