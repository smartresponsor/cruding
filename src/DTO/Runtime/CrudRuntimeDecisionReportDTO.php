<?php

declare(strict_types=1);

namespace App\Cruding\DTO\Runtime;

/**
 * Cross-source runtime decision report for Cruding deploy checks.
 */
final readonly class CrudRuntimeDecisionReportDTO
{
    /**
     * @param list<string>          $errors
     * @param list<string>          $warnings
     * @param array<string, string> $expectedPackageByScopeToken
     */
    public function __construct(
        public CrudRuntimeRouteGuardPolicyDTO $routePolicy,
        public CrudRuntimeLockDTO $runtimeLock,
        public CrudRuntimeComposerInventoryDTO $composerInventory,
        public array $expectedPackageByScopeToken,
        public array $errors,
        public array $warnings,
    ) {
    }

    /**      * Executes the passed operation.      */
    public function passed(): bool
    {
        return [] === $this->errors;
    }
}
