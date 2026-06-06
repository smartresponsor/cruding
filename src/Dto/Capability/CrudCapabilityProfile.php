<?php

declare(strict_types=1);

namespace App\Cruding\Dto\Capability;

final readonly class CrudCapabilityProfile
{
    /** @param array<string, CrudCapabilityMatch> $matches */
    public function __construct(
        public string $className,
        public array $matches,
    ) {
    }

    public function supports(string $capability): bool
    {
        return $this->match($capability)->supported;
    }

    public function match(string $capability): CrudCapabilityMatch
    {
        return $this->matches[$capability] ?? new CrudCapabilityMatch($capability, false);
    }
}
