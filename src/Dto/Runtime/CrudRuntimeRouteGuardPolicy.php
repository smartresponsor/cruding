<?php

declare(strict_types=1);

namespace App\Cruding\Dto\Runtime;

/**
 * Immutable route guard policy derived from runtime scope/entity/surface tokens.
 */
final readonly class CrudRuntimeRouteGuardPolicy
{
    /**
     * @param list<string> $scopeTokens
     * @param list<string> $entityTokens
     * @param list<string> $surfaceTokens
     * @param list<string> $reservedRootTokens
     * @param list<string> $allowedResourceTokens
     * @param list<string> $conflictingEntityTokens
     */
    public function __construct(
        public array $scopeTokens,
        public array $entityTokens,
        public array $surfaceTokens,
        public array $reservedRootTokens,
        public array $allowedResourceTokens,
        public array $conflictingEntityTokens,
        public string $resourceRequirement,
        public string $resourcePathRequirement,
        public string $surfaceTokenRequirement,
    ) {
    }

    public function hasConflicts(): bool
    {
        return [] !== $this->conflictingEntityTokens;
    }
}
