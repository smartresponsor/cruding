<?php

declare(strict_types=1);

namespace App\Cruding\Service\Crud\Runtime;

use App\Cruding\Dto\Runtime\CrudRuntimeRouteGuardPolicy;

final readonly class CrudRuntimeRouteGuard
{
    /**
     * @param list<string> $scopeTokens
     * @param list<string> $entityTokens
     * @param list<string> $surfaceTokens
     * @param list<string> $reservedRootTokens
     * @param list<string> $operationTokens
     * @param list<string> $resourcePathReservedTokens
     * @param list<string> $allowedResourceTokens
     * @param list<string> $conflictingEntityTokens
     */
    public function __construct(
        private array $scopeTokens,
        private array $entityTokens,
        private array $surfaceTokens,
        private array $reservedRootTokens,
        private array $operationTokens,
        private array $resourcePathReservedTokens,
        private array $allowedResourceTokens,
        private array $conflictingEntityTokens,
        private string $resourceRequirement,
        private string $resourcePathRequirement,
        private string $surfaceTokenRequirement,
        private string $identitySlugRequirement,
    ) {
    }

    public function policy(): CrudRuntimeRouteGuardPolicy
    {
        return new CrudRuntimeRouteGuardPolicy(
            scopeTokens: $this->scopeTokens,
            entityTokens: $this->entityTokens,
            surfaceTokens: $this->surfaceTokens,
            reservedRootTokens: $this->reservedRootTokens,
            operationTokens: $this->operationTokens,
            resourcePathReservedTokens: $this->resourcePathReservedTokens,
            allowedResourceTokens: $this->allowedResourceTokens,
            conflictingEntityTokens: $this->conflictingEntityTokens,
            resourceRequirement: $this->resourceRequirement,
            resourcePathRequirement: $this->resourcePathRequirement,
            surfaceTokenRequirement: $this->surfaceTokenRequirement,
            identitySlugRequirement: $this->identitySlugRequirement,
        );
    }
}
