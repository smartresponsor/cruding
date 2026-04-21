<?php

declare(strict_types=1);

namespace App\Cruding\Dto\Capability;

final readonly class CapabilityProfile
{
    /** @param array<string, CapabilityMatch> $matches */
    public function __construct(
        public string $className,
        public array $matches,
    ) {
    }

    public function supports(string $capability): bool
    {
        return $this->match($capability)->supported;
    }

    public function match(string $capability): CapabilityMatch
    {
        return $this->matches[$capability] ?? new CapabilityMatch($capability, false);
    }
}
