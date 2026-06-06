<?php

declare(strict_types=1);

namespace App\Cruding\Dto\Runtime;

/**
 * Cross-source runtime decision report for Cruding deploy checks.
 */
final readonly class CrudRuntimeDecisionReport
{
    /**
     * @param list<string>          $errors
     * @param list<string>          $warnings
     * @param array<string, string> $expectedPackageByScopeToken
     */
    public function __construct(
        public CrudRuntimeRouteGuardPolicy $routePolicy,
        public CrudRuntimeLock $runtimeLock,
        public CrudRuntimeComposerInventory $composerInventory,
        public array $expectedPackageByScopeToken,
        public array $errors,
        public array $warnings,
    ) {
    }

    public function passed(): bool
    {
        return [] === $this->errors;
    }
}
