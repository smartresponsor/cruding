<?php

declare(strict_types=1);

namespace App\Cruding\Dto\Capability;

final readonly class CapabilityMatch
{
    public function __construct(
        public string $capability,
        public bool $supported,
        public string $confidence = 'none',
        public ?string $memberName = null,
        public ?string $memberKind = null,
        public ?string $interfaceName = null,
    ) {
    }
}
