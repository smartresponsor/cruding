<?php

declare(strict_types=1);

namespace App\Cruding\DTO\Capability;

/**
 * Carries capability profile data across Cruding processing boundaries.
 */
final readonly class CrudCapabilityProfileDTO
{
    /** @param array<string, CrudCapabilityMatchDTO> $matches */
    public function __construct(
        public string $className,
        public array $matches,
    ) {
    }

    /**      * Indicates whether this implementation supports the supplied context.      */
    public function supports(string $capability): bool
    {
        return $this->match($capability)->supported;
    }

    /**      * Executes the match operation.      */
    public function match(string $capability): CrudCapabilityMatchDTO
    {
        return $this->matches[$capability] ?? new CrudCapabilityMatchDTO($capability, false);
    }
}
