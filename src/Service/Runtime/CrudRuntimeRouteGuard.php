<?php

declare(strict_types=1);

namespace App\Cruding\Service\Runtime;

use App\Cruding\Dto\Runtime\CrudRuntimeRouteGuardPolicy;

/**
 * Runtime-facing access point for the already canonical route guard policy.
 */
final readonly class CrudRuntimeRouteGuard
{
    /**
     * @param list<string> $scopeTokens
     * @param list<string> $entityTokens
     * @param list<string> $viewTokens
     * @param list<string> $reservedRootTokens
     * @param list<string> $operationTokens
     * @param list<string> $resourcePathReservedTokens
     * @param list<string> $allowedResourceTokens
     * @param list<string> $conflictingEntityTokens
     */
    public function __construct(
        private array $scopeTokens,
        private array $entityTokens,
        private array $viewTokens,
        private array $reservedRootTokens,
        private array $operationTokens,
        private array $resourcePathReservedTokens,
        private array $allowedResourceTokens,
        private array $conflictingEntityTokens,
        private string $resourceRequirement,
        private string $resourcePathRequirement,
        private string $viewTokenRequirement,
        private string $identitySlugRequirement,
    ) {
    }

    public function policy(): CrudRuntimeRouteGuardPolicy
    {
        return new CrudRuntimeRouteGuardPolicy(
            scopeTokens: $this->scopeTokens,
            entityTokens: $this->entityTokens,
            viewTokens: $this->viewTokens,
            reservedRootTokens: $this->reservedRootTokens,
            operationTokens: $this->operationTokens,
            resourcePathReservedTokens: $this->resourcePathReservedTokens,
            allowedResourceTokens: $this->allowedResourceTokens,
            conflictingEntityTokens: $this->conflictingEntityTokens,
            resourceRequirement: $this->resourceRequirement,
            resourcePathRequirement: $this->resourcePathRequirement,
            viewTokenRequirement: $this->viewTokenRequirement,
            identitySlugRequirement: $this->identitySlugRequirement,
        );
    }
}
